<?php

namespace Aragon\Escolares\Servicios\Validaciones;

use Aragon\Escolares\Entity\Servicios\ConfigServicio;
use DateTime;
use DateTimeZone;

/**
 * Validación del Horario de Atención de Servicios Escolares.
 *
 * @category    Validaciones
 *
 * @author      Alexei Emmanuel <alexeiemmanuel@aragon.unam.mx>
 * @author      Francisco Dante <desarrollo@aragon.unam.mx>
 * @copyright   Copyright (c) 2020  (https://www.acceso.aragon.unam.mx/)
 */
class ValidacionHorario extends Validacion
{
    /**
     * Referencia del tipo ConfigServicio.
     * 
     * @var ConfigServicio 
     */
    private $estadoMaestro;

    /**
     * Constructor de la clase.
     * 
     * @param ConfigServicio $estadoMaestro
     */
    public function __construct(ConfigServicio $estadoMaestro)
    {
        $this->estadoMaestro = $estadoMaestro;
        $this->nombre = "Horario";
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorMensaje($debug = false): string
    {
        return $this->mensajeError.($debug ? '('.__CLASS__.')' : '');
    }

    /**
     * {@inheritdoc}
     */
    public function getExitoMensaje(): string
    {
        return 'Validacion Horario superada con éxito.';
    }

    /**
     * {@inheritdoc}
     */
    public function servicioDisponible(): bool
    {
        $fechaActual = new DateTime('now', new DateTimeZone('America/Mexico_City'));
        $dia         = $fechaActual->format('w');
        if ('0' == $dia || '6' == $dia) {
            $this->mensajeError = 'El horario para iniciar solicitudes de este trámite es de '.$this->estadoMaestro->getHoraAtencionInicio()->format('H:i').' a '.$this->estadoMaestro->getHoraAtencionFin()->format('H:i').' de lunes a viernes. Te pedimos que lo intentes dentro de este horario.';

            return false;
        } else {
            if (strtotime($this->estadoMaestro->getHoraAtencionInicio()->format('H:i:s')) <= strtotime($fechaActual->format('H:i:s')) && strtotime($fechaActual->format('H:i:s')) <= strtotime($this->estadoMaestro->getHoraAtencionFin()->format('H:i:s'))) {
                return true;
            } else {
                $this->mensajeError = 'El horario para iniciar solicitudes de este trámite es de '.$this->estadoMaestro->getHoraAtencionInicio()->format('H:i A').' a '.$this->estadoMaestro->getHoraAtencionFin()->format('H:i A').' de lunes a viernes. Te pedimos que lo intentes dentro de este horario.';

                return false;
            }
        }
    }
}
