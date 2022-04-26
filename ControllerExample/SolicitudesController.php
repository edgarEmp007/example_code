<?php

namespace TramifesFrontend\SolicitudesBundle\Controller;

use Aragon\Escolares\Enums\Servicios\EnumEstadoSolicitud;
use Aragon\Escolares\Servicios\Titulacion\TitTiposSolicitudUtilities;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Tramifes\ClassesBundle\Classes\ArchivoDigital\ArchivoDigitalManager;
use Aragon\Bloques\Folder;
use Tramifes\ClassesBundle\Classes\Titulacion\PasoTitulacionTramite;

/**
 * Clase abstracta SolicitudesController.
 *
 * @category    Solicitudes
 *
 * @author      Edgar Macias<edgaremp007@gmail.com>
 * @copyright   Copyright (c) 2020  (https://www.acceso.aragon.unam.mx/)
 */

/**
 * @Route(path="/alumnos/solicitudes")
 */
class SolicitudesController extends Controller
{
    /**
     * @Route(path="/", name="init-route-solicitudes")
     */
    public function indexRoute(){}

    /**
     * @Route(path="/{clave}", name="home-solicitud")
     */
    public function homeAction(Request $request, $clave)
    {
        $sb              = $this->get('solicitud.build');//instance for SolicitudesBuilder set as symfony service
        $alumnoId        = $this->getUser()->getId();
        $alumnoHasPlanes = $request->getSession()->get('planesEstudios');

        $solConstancia = $sb->buildSolicitudByClave($clave);
        //TODO Verificar que la clave enviada regrese un obj solicitud y en caso de que no regresar que la solicitud que intenta iniciar no existe

        if (null == $solConstancia) {
            //Si no existe una solicitud redirige a la pagina principal
            return $this->redirect($this->generateUrl('index-solicitudes'));
        }

        $solConstancia->setAlumno($alumnoId);
        $solConstancia->setPlan($alumnoHasPlanes[0]->getPlanEstudios()->getId());

        $solConstancia->getPasosSolicitud();
        $solConstancia->getDocVentanillas();
        $solConstancia->getDocOnline();

        $solEnProceso          = $solConstancia->hasSolicitudProceso();
        $validacionServicio    = false; //Suponemos que el alumno no puede realizar la solicitud
        //Variables para la fecha de entrega de resultado en caso de que la solicitud tenga
        $fechaInicioResultados = null;
        $fechaFinResultados    = null;
        $showResultado         = false;
        $incidencias           = [];
        $urlComprobante        = null;
        if (null != $solConstancia->getUrlComprobante()) {
            $urlComprobante = $this->generateUrl($solConstancia->getUrlComprobante(), ['plan' => $solConstancia->getPlan()]);
        }

        if (!$solEnProceso) {
            $solConstancia->construirValidaciones();
            $validacionServicio = $solConstancia->validar();
        } else {
            //Si la solicitud existe verificamos la fecha de entrega de resultado en caso de que tenga
            $em      = $this->getDoctrine()->getManager();
            $solTipo = $em->getRepository('AragonEscServicios:TipoSolicitud')->find($clave);

            if (true == $solTipo->getIsCalendarizado()) {
                $solCalendario = $em->getRepository('AragonEscServicios:Calendario')->findOneBy(['tipoSolicitud' => $clave]);

                if (null != $solCalendario) {
                    $fechaInicioResultados = $solCalendario->getFechaInicioResultados();
                    $fechaFinResultados    = $solCalendario->getFechaFinResultados();
                    $hoy                   = new DateTime();

                    if ($hoy >= $fechaInicioResultados && $hoy <= $fechaFinResultados) {
                        $showResultado = true;
                    } else {
                        $showResultado = false;
                    }
                }
            }

            if (sizeof($solConstancia->getSolicitud()->getIncidencias()) > 0) {
                foreach ($solConstancia->getSolicitud()->getIncidencias() as $i) {
                    if (null != $i->getArchivoDigital()) {
                        $incidencias[] = $i->getArchivoDigital()->getDocumento()->getTipo().': '.$i->getDescripcion();
                    } else {
                        $incidencias[] = $i->getDescripcion();
                    }
                }
            }
        }

        $parameters = [
            'solicitud'            => $solConstancia,
            'validacionServicio'   => $validacionServicio,
            'hasSolicitud'         => $solEnProceso,
            'fechaInicioResultado' => $fechaInicioResultados,
            'fechaFinResultado'    => $fechaFinResultados,
            'showResultado'        => $showResultado,
            'jsonSolicitud'        => json_encode($solConstancia->getSolicitud()),
            'hasDocumentos'        => $solConstancia->getSolicitudTipo()->getHasDocumentos(),
            'incidencias'          => json_encode($incidencias),
            'comprobante'          => $urlComprobante];

        return $this->render('view.html.twig',
                        $parameters);
    }
}
