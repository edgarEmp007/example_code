<?php

namespace Aragon\Escolares\Servicios\Solicitudes;

use Aragon\Escolares\Entity\Servicios\TipoSolicitud;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Description of SolicitudBuildier.
 *
 * @author edgar macias
 */
class SolicitudBuildier
{
    /**
     * EntityManagerInterface para realizar la conexión a la D.B.
     *
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * Ruta donde se alojan las imágenes.
     *
     * @var string
     */
    protected $ruta_fotos_credenciales;

    /**
     * Constructor de la clase.
     *
     * @param EntityManagerInterface $entityManager Conexión a la D.B.
     * @param string                 $rutaFotos     ruta donde se alojan las imágenes
     */
    public function __construct(EntityManagerInterface $entityManager, string $rutaFotos)
    {
        $this->em                      = $entityManager;
        $this->ruta_fotos_credenciales = $rutaFotos;
    }

    public function getEntityManager()
    {
        return $this->em;
    }

    public function constanciaEstudios()
    {
        return new SolConstanciaEstudios($this->em);
    }

    public function constanciaCreditos()
    {
        return new SolConstanciaCreditos($this->em);
    }

    public function constanciaHisAcademica()
    {
        return new SolConstanciaHistoriaAcademica($this->em);
    }

    public function certificado()
    {
        return new SolCertificado($this->em);
    }

    public function cartaPasante()
    {
        return new SolCartaPasante($this->em);
    }

    public function suspencionTemporalEstudios()
    {
        return new SolSuspencionTemporalEstudios($this->em);
    }

    public function revisionEstudios()
    {
    }

    public function fechaExamen()
    {
    }

    public function credencial()
    {
        return new SolCredencial($this->em, $this->ruta_fotos_credenciales);
    }

    public function areaTerminal()
    {
        return new SolAreaTerminal($this->em);
    }

    public function solPermisoExtrasAdicionales()
    {
        return new SolRegistroExtrasAdicionales($this->em);
    }

    public function actualizarDatosAlumno()
    {
        return new SolActualizarDatosAlumno($this->em);
    }

    public function cambioTurno()
    {
        return new SolCambioTurno($this->em);
    }

    public function constanciaEstudiosEspeciales()
    {
        return new SolConstanciaEstudiosEspeciales($this->em);
    }

    public function horarioReinscripcion()
    {
        return new SolHorarioReinscripcion($this->em);
    }

    public function horarioAltasYBajas()
    {
        return new SolHorarioAltasYBajas($this->em);
    }

    public function cambioContrasenia()
    {
        return new SolCambioContrasenia($this->em);
    }

    public function pagoCuotaAnual()
    {
        return new SolPagoCuotaAnual($this->em);
    }

    public function buildSolicitudByClave($clave)
    {
        $clave =  is_numeric($clave) ? $clave : '0';
        switch ($clave) {
            case TiposSolicitud::CONSTANCIA_ESTUDIOS:
                return $this->constanciaEstudios();
            case TiposSolicitud::CONSTANCIAS_CREDITOS:
                return $this->constanciaCreditos();
            case TiposSolicitud::CONSTANCIA_HIST_ACADEMICA:
                return $this->constanciaHisAcademica();
            case TiposSolicitud::CERTIFICADO:
                return $this->certificado();
            case TiposSolicitud::CARTA_PASANTE:
                return $this->cartaPasante();
            case TiposSolicitud::SUSPENSION_TEMPORAL:
                return $this->suspencionTemporalEstudios();
            case TiposSolicitud::CREDENCIAL:
                return $this->credencial();
            case TiposSolicitud::SELECCION_AREA_TERMINAL:
                return $this->areaTerminal();
            case TiposSolicitud::PERMISO_REGISTRO_EXTRAS:
                return $this->solPermisoExtrasAdicionales();
            case TiposSolicitud::ACTUALIZAR_DATOS_ALUMNO:
                return $this->actualizarDatosAlumno();
            case TiposSolicitud::CAMBIO_TURNO:
                return $this->cambioTurno();
            case TiposSolicitud::CONSTANCIA_ESTUDIOS_ESPECIAL:
                return $this->constanciaEstudiosEspeciales();
            case TiposSolicitud::HORARIO_SORTEO_REINSCRIPCION:
                return $this->horarioReinscripcion();
            case TiposSolicitud::HORARIO_SORTEO_ALTAS_BAJAS:
                return $this->horarioAltasYBajas();
            case TiposSolicitud::RECUPERAR_CONTRASENA:
                return $this->cambioContrasenia();
            case TiposSolicitud::PAGO_CUOTA_ANUAL:
                return $this->pagoCuotaAnual();
            default:
                return null;
        }
    }

    /*
     * Construye todas las solicitudes disponibles y las retorna en un array
     *
     */
    public function buildSolicitudesTodas()
    {
        $solicitudes = [];

        $solicitudes[] = $this->constanciaEstudios();
        $solicitudes[] = $this->constanciaEstudiosEspeciales();
        $solicitudes[] = $this->constanciaCreditos();
        $solicitudes[] = $this->constanciaHisAcademica();

        $solicitudes[] = $this->cartaPasante();
        $solicitudes[] = $this->suspencionTemporalEstudios();
        $solicitudes[] = $this->cambioTurno();
        $solicitudes[] = $this->credencial();
        $solicitudes[] = $this->solPermisoExtrasAdicionales();
        $solicitudes[] = $this->areaTerminal();
        $solicitudes[] = $this->horarioReinscripcion();

        return $solicitudes;
    }
}
