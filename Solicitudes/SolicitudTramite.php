<?php

namespace Aragon\Escolares\Servicios\Solicitudes;

use Aragon\Escolares\Entity\Servicios\Solicitud;
use DateTime;
use Exception;

/**
 * Clase SolicitudTramite.
 *
 * @author edgar macias
 */
abstract class SolicitudTramite
{
    protected $em;
    protected $estado;
    protected $pasos                 = ['paso por default'];
    protected $documentosRequeridos  = [];
    protected $docDigitales          = [];
    protected $docVentanilla         = [];
    protected $validaciones          = [];
    protected $resValidacionCompleta = []; //Almacena el resultado de todas la validaciones que fallaron
    protected $mensajeValidacion;
    protected $solicitudTipo;
    protected $solicitud;
    protected $alumno;
    protected $plan;
    protected $cicloEscolar;
    protected $routeComprobante = null;
    protected $calendario;

    protected $debugMode = false;
    protected $nombre;

    /**
     * Método abstracto que construye las validaciones dependiendo de la solicitud.
     */
    abstract public function construirValidaciones($agregaSolicitudEnTramite = false,$isFromDebugger = false);

    /**
     * Método abstracto que abstracto que obtiene una template Twig en caso que la solicitud lo
     * soporte.
     */
    abstract public function getFormatoController();

    /**
     * Método abstracto que abstracto que obtiene el script angular en caso que la solicitud lo
     * soporte.
     */
    abstract public function getAngularController();
      
    /**
     * Método abstracto que abstracto construye el mensaje de acuerdo el estado de la solicitud
     * siempre y cuando el alumno tiene una solicitud en proceso 
     */
    abstract public function construyeCalendarioMensaje(): ?array;

    /**
     * Método abstracto para devolver la información especifica de una solicitud cuando
     * es solicitada por el Revisor de Solicitudes.
     */
    public function construyeDataRevision()
    {
        $dataAdicional  = $this->getDataAdicionalRevision();
        $dataDocumentos = $this->getArchivoDigRevision();

        return (object) array_merge($dataAdicional, $dataDocumentos);
    }

    /**
     * Revisa si ya se tienen todos  los documentos que necesita esta solicitud en el archivo digital
     * Si ya existen regresa true. Este método se utiliza para saber si el alumno cumple con todos
     * los documentos digitales y si se puede iniciar la solicitud. Antes de llamar a este método es
     * necesario llamar a getDocOnline para que los documentos estén presentes.
     *
     * @return bool
     */
    public function checkDocDigitales(): bool
    {
        //La solicitud no tiene documentos digitales por lo tanto puede ser iniciada
        if (count($this->docDigitales) <= 0) {
            return true;
        }

        //Suponemos que tiene todos los documentos
        $cumpleDocumentacion = true;
        foreach ($this->docDigitales as $documento) {
            $archivo = $this->em->getRepository('AragonEscServicios:ArchivoDigital')
                                ->findAlumnoPlanDocumento($this->alumno, $this->plan, $documento->getId());

            if (null == $archivo) {
                $cumpleDocumentacion = false;
                break; //No es necesario buscar los otros documentos
            }
        }

        return $cumpleDocumentacion;
    }

    /**
     * Método que devuelve los pasos de la solicitud.
     */
    public function getPasosSolicitud()
    {
        $this->pasos = $this->em->getRepository('AragonEscServicios:PasoSolicitud')
                                ->getPasosByTipoSolicitud($this->solicitudTipo->getId());
    }

    /**
     * Método que devuelve los documentos requeridos que son para ventanillas.
     */
    public function getDocVentanillas()
    {
        $this->docVentanilla = $this->em->getRepository('AragonEscServicios:Documento')
                                        ->getAllDocumentosByTipoSolicitudInDigital(false,
                                                                                   $this->plan,
                                                                                   $this->solicitudTipo->getId());
    }

    /**
     * Método que devuelve los documentos requeridos que son para archivo digital.
     */
    public function getDocOnline()
    {
        $this->docDigitales = $this->em->getRepository('AragonEscServicios:Documento')
                                       ->getAllDocumentosByTipoSolicitudInDigital(true,
                                                                                  $this->plan,
                                                                                  $this->solicitudTipo->getId());
    }

    /**
     * Método que válida si el usuario tiene una solicitud previa.
     *
     * @return bool
     */
    public function hasSolicitudProceso(): bool
    {
        $solicitud = $this->em->getRepository('AragonEscServicios:Solicitud')
                              ->getSolicitudByAlumnoPlanTipoCiclo($this->alumno,
                                                                  $this->plan,
                                                                  $this->solicitudTipo->getId(),
                                                                  EstadosSolicitudes::ESTADOS_SOLICITUD_EN_PROCESO,
                                                                  $this->cicloEscolar->getId());

        if (is_object($solicitud)) {
            $this->setSolicitud($solicitud);
            $this->setMensajeValidacion('Tienes una solicitud en proceso');

            return true;
        } else {
            $this->setSolicitud(null);

            return false;
        }
    }

    /**
     * Método que valida una por una Validaciones asociadas a esta
     * Solicitud. Retorna inmediatamente después de que se encuentra
     * la primera validación que falla y las Validaciones restantes se
     * ignoran.
     *
     * @return bool
     *
     * @throws Exception
     */
    public function validar(): bool
    {
        $resValidaciones = true;
        if (count($this->validaciones) > 0) {
            foreach ($this->validaciones as $valServicio) {
                $resValidaciones = $valServicio->servicioDisponible();

                if (false == $resValidaciones) {
                    $this->mensajeValidacion = $valServicio->getErrorMensaje($this->debugMode);

                    return $resValidaciones;
                }
            }
        } else {
            throw  new Exception('No hay validaciones que necesiten ser verificadas. ¿Olvidaste llamar a construirValidaciones() en tu Solicitud?');
        }

        return $resValidaciones;
    }

    /**
     * Valida todas las Validaciones asociadas a esta Solicitud
     * sin importar que encuentre alguna validación que falle.
     * Se utiliza con propósitos de depuración para saber
     * todos los motivos por lo que una alumno no podría realizar
     * un tramite.
     *
     * @return array
     *
     * @throws Exception
     */
    public function validacionCompleta(): ?array
    {
        if (count($this->validaciones) > 0) {
            foreach ($this->validaciones as $clave => $valServicio) {
                $resValidaciones = $valServicio->servicioDisponible();

                if (false == $resValidaciones) {
                    $this->resValidacionCompleta[$clave] = $valServicio->getErrorMensaje($this->debugMode);
                }
            }
        } else {
            throw  new Exception('No hay validaciones que necesiten ser verificadas. ¿Olvidaste llamar a construirValidaciones() en tu Solicitud?');
        }

        return $this->resValidacionCompleta; //Todas las Validaciones se validaron
    }

    /**
     * Valida que la cantidad de documentos solicitados por el alumno sean validos.
     *
     * @param int $docCantidad número de documentos solicitados por el alumno
     *
     * @return int
     */
    public function validarDocCantidad(int $docCantidad): int
    {
        $cantidadMax = $this->solicitudTipo->getNumMaxAlumno();
        if ($docCantidad <= 0) {
            return 1;
        } elseif ($docCantidad > $cantidadMax) {
            return $cantidadMax;
        } else {
            return $docCantidad;
        }
    }

    /**
     * Método que guarda la solicitud del alumno con los valores que solicito.
     *
     * @param array $valores Array que contiene los valores para el llenado de la solicitud
     *
     * @return bool
     */
    public function guardarSolicitud(array $valores): bool
    {
        $docCantidad = $this->validarDocCantidad($valores['docCantidad']);
        $alumnoPlan  = $this->em->getReference('AragonEscPublico:AlumnoHasPlanEstudios', [
            'alumno'       => $this->alumno,
            'planEstudios' => $this->plan]);

        //Guardar nueva solicitud
        $newSol = new Solicitud();
        $newSol->setCicloEscolar($this->cicloEscolar);
        $newSol->setTipoSolicitud($this->getSolicitudTipo());
        $newSol->setPlanAlumno($alumnoPlan);
        $newSol->setIsColor(false);
        $newSol->setNumDocumentos($docCantidad);       
        isset($valores['alumno'])?$newSol->setEstadoSolicitud('ENTREGADO'):$newSol->setEstadoSolicitud('SOLICITUD INICIADA');
        $newSol->setEstadoPrevio(null);
        $newSol->setCreatedAt(new DateTime());
        $newSol->setMedioInicio('WEB');

        $this->em->persist($newSol);
        $this->em->flush();

        $this->solicitud = $newSol;

        return true;
    }

    /* METODOS PARA CONSTRUIR LA INFORMACION DE LA SOLICITUD CUANDO SE ESTA REVISANDO */

    public function getDataAdicionalRevision()
    {
        $dataRevision = [];

        $dataRevision['turno']             = $this->solicitud->getPlanAlumno()->getTurno()->getNombre();
        $dataRevision['estado']            = $this->solicitud->getEstadoSolicitud();
        $dataRevision['hasDocumentos']     = $this->solicitudTipo->getHasDocumentos();
        $dataRevision['hasInfoEspecifica'] = $this->solicitudTipo->getHasInfoEspecifica();
        $dataRevision['incidencias']       = $this->em->getRepository('AragonEscServicios:Incidencias')->findBy(['solicitud' => $this->solicitud]);

        return $dataRevision;
    }

    public function getArchivoDigRevision()
    {
        $dataRevision = [];
        if ($this->solicitudTipo->getHasDocumentos()) {
            $dataRevision['docDigitales'] = $this->em->getRepository('AragonEscServicios:ArchivoDigital')->
                    getArchivosDigitalByAlumnoPlanSolicitud($this->alumno,
                    $this->plan, $this->solicitudTipo->getId(), $this->solicitud->getId());
        } else {
            $dataRevision['docDigitales'] = '';
        }

        return $dataRevision;
    }

    /**
     * Metodo que retorna los archivos adicionales de la opcion de titulacion.
     *
     * @param $tesis
     * @return array
     */
    public function getArchivosAdicionalesTitulacion($tesis): array {
        $tesisArchivosAdicionales = $this->em->getRepository('AragonEscTitulacion:TesisArchivosAdicionales')
            ->getArchivosAdicionalesByTesisId($tesis->getId());
        $archivosReturnedValue = [];
        if($tesisArchivosAdicionales!=null){
            foreach ($tesisArchivosAdicionales as $tAA){
                $archivosReturnedValue[]=array('id' => $tAA->getId(),'tipo'=>$tAA->getTipo(),'extension'=>$tAA->getExtension(),
                    'nombreArchivo'=> $tAA->getNombreArchivo(),'nombreOriginal'=>$tAA->getNombreOriginal());
            }
            return $archivosReturnedValue;
        }else{
            return [];
        }

    }

    public function getEstado()
    {
        return $this->estado;
    }

    public function getPasos()
    {
        return $this->pasos;
    }

    public function getDocumentosRequeridos()
    {
        return $this->documentosRequeridos;
    }

    public function getArchivoDigital()
    {
        return $this->archivoDigital;
    }

    public function getValidacionesServicio()
    {
        return $this->validacionesServicio;
    }

    public function getValidacionesSolicitud()
    {
        return $this->validacionesSolicitud;
    }

    public function setEstado($estado)
    {
        $this->estado = $estado;
    }

    public function setPasos($pasos)
    {
        $this->pasos = $pasos;
    }

    public function setDocumentosRequeridos($documentosRequeridos)
    {
        $this->documentosRequeridos = $documentosRequeridos;
    }

    public function setArchivoDigital($archivoDigital)
    {
        $this->archivoDigital = $archivoDigital;
    }

    public function setValidacionesServicio($validacionesServicio)
    {
        $this->validacionesServicio = $validacionesServicio;
    }

    public function setValidacionesSolicitud($validacionesSolicitud)
    {
        $this->validacionesSolicitud = $validacionesSolicitud;
    }

    public function getSolicitudTipo()
    {
        return $this->solicitudTipo;
    }

    public function setSolicitudTipo($solicitudTipo)
    {
        $this->solicitudTipo = $solicitudTipo;
    }

    public function getSolicitud()
    {
        return $this->solicitud;
    }

    public function setSolicitud($solicitud)
    {
        $this->solicitud = $solicitud;
    }

    public function getAlumno()
    {
        return $this->alumno;
    }

    public function setAlumno($alumno)
    {
        $this->alumno = $alumno;
    }

    public function getPlan()
    {
        return $this->plan;
    }

    public function setPlan($plan)
    {
        $this->plan = $plan;
    }

    public function getMensajeValidacion()
    {
        return $this->mensajeValidacion;
    }

    public function setMensajeValidacion($mensajeValidacion)
    {
        $this->mensajeValidacion = $mensajeValidacion;
    }

    public function getCicloEscolar()
    {
        return $this->cicloEscolar;
    }

    public function setCicloEscolar($cicloEscolar)
    {
        $this->cicloEscolar = $cicloEscolar;
    }

    public function getDocDigitales()
    {
        return $this->docDigitales;
    }

    public function getDocVentanilla()
    {
        return $this->docVentanilla;
    }

    public function setDocDigitales($docDigitales)
    {
        $this->docDigitales = $docDigitales;
    }

    public function setDocVentanilla($docVentanilla)
    {
        $this->docVentanilla = $docVentanilla;
    }

    public function getUrlComprobante()
    {
        return $this->routeComprobante;
    }

    public function setUrlComprobante($urlComprobante)
    {
        $this->routeComprobante = $urlComprobante;
    }

    public function getValidaciones()
    {
        return $this->validaciones;
    }

    public function getResValidacionCompleta()
    {
        return $this->resValidacionCompleta;
    }

    public function getDebugMode()
    {
        return $this->debugMode;
    }

    public function setValidaciones($validaciones)
    {
        $this->validaciones = $validaciones;
    }

    public function setResValidacionCompleta($resValidacionCompleta)
    {
        $this->resValidacionCompleta = $resValidacionCompleta;
    }

    public function setDebugMode($debugMode)
    {
        $this->debugMode = $debugMode;
    }

    public function getNombre()
    {
        return $this->nombre;
    }
    
    public function getCalendario()
    {
        return $this->calendario;
    }

    public function setCalendario($calendario): void
    {
        $this->calendario = $calendario;
    }


}
