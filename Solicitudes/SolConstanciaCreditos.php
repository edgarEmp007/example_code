<?php

namespace Aragon\Escolares\Servicios\Solicitudes;

use Aragon\Escolares\Servicios\Validaciones\ValidacionActualizarDatos;
use Aragon\Escolares\Servicios\Validaciones\ValidacionEstadoMaestro;
use Aragon\Escolares\Servicios\Validaciones\ValidacionHorario;
use Aragon\Escolares\Servicios\Validaciones\ValidacionLimiteSolicitudes;
use Aragon\Escolares\Servicios\Validaciones\ValidacionSolicitudProceso;
use Aragon\Escolares\Servicios\Validaciones\ValidacionAvanceCreditos;
use Doctrine\ORM\EntityManager;

/**
 * Description of SolConstanciaCreditos.
 *
 * @author edgar macias
 */
class SolConstanciaCreditos extends SolicitudTramite
{
    public function __construct(EntityManager $entitiManager)
    {
        $this->em = $entitiManager;
        //Obtenemos el tipo de solicitud
        $this->solicitudTipo = $this->em->getRepository('AragonEscServicios:TipoSolicitud')->find(2);
        //Se obtiene el ciclo escolar del schema servicios
        $this->cicloEscolar = $this->em->getRepository('AragonEscServicios:CicloEscolarServicios')->getCicloActual();

        $this->nombre = 'Constancia de Créditos';
    }

    /**
     * {@inheritdoc}
     */
    public function construirValidaciones($agregaSolicitudEnTramite = false, $isFromDebugger = false)
    {
        //Se obtiene el estado maestro
        $estadoMaestro = $this->em->getRepository('AragonEscServicios:ConfigServicio')->getEstadoServicio();
               
        //Creamos las validaciones de este tipo de solicitud
        if(!$isFromDebugger)
        {
        $this->validaciones[] = new ValidacionHorario($estadoMaestro);
        $this->validaciones[] = new ValidacionActualizarDatos($this->em, $this->alumno);
        }
        if (true == $agregaSolicitudEnTramite) {
            $this->validaciones[] = new ValidacionSolicitudProceso($this->em, $this->alumno, $this->plan, $this->solicitudTipo->getId(),
                                                               ['SOLICITUD INICIADA', 'LISTO PARA GENERAR', 'EN TRAMITE', 'LISTO PARA ENTREGA', 'SUSPENDIDO POR INCIDENCIA', 'RECHAZADA'],
                                                               $this->cicloEscolar->getId());
        }
        $this->validaciones[] = new ValidacionEstadoMaestro($estadoMaestro);
        $this->validaciones[] = new ValidacionLimiteSolicitudes($this->em, $this->solicitudTipo, $this->cicloEscolar->getId());
        $this->validaciones[] = new ValidacionAvanceCreditos($this->em, $this->alumno, $this->plan);
    }

    /**
     * {@inheritdoc}
     */
    public function getAngularController()
    {
        return '/js/apps/solicitudes/constanciaCreditos.js';
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatoController()
    {
        return 'TramifesFrontendSolicitudesBundle:ConstanciaCreditos:formato';
    }

    /**
     * {@inheritdoc}
     */
    public function checkDocDigitales(): bool
    {
        //La solictud no tiene ningun archivo digital, por lo tanto el alumno cumple con los requisitos y se regresa true
        return true;
    }
    
    /**
     * {@inheritdoc}
     */
    public function construyeCalendarioMensaje(): ?array
    {
        return ['showResultado' => true, 'mensaje' => ''];
    }
}
