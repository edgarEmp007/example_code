<?php

namespace Aragon\Escolares\Servicios\Validaciones;

use Aragon\Escolares\Entity\Publico\CicloEscolar;
use Doctrine\ORM\EntityManager;
use Exception;

/**
 * Validación ActualizarDatos Esta validación es la única que se compara la tabla ciclo_escolar del
 * schema public de la base de datos.
 *
 * @category    Validaciones
 *
 * @author      Edgar MAcias <edgaremp007@gmail.com>
 * @copyright   Copyright (c) 2020  (https://www.acceso.aragon.unam.mx/)
 */
class ValidacionActualizarDatos extends Validacion
{
    /**
     * Número de cuenta del alumno.
     *
     * @var string
     */
    private $idAlumno;

    /**
     * Referencia CicloEscolar.
     *
     * @var CicloEscolar
     */
    private $cicloEscolar;

    /**
     * Constructor de la clase.
     *
     * @param EntityManager $entitiManager Conexión a la D.B.
     * @param string        $idAlumno      Número de cuenta del alumno
     */
    public function __construct(EntityManager $entitiManager, string $idAlumno)
    {
        $this->em       = $entitiManager;
        $this->idAlumno = $idAlumno;
        $this->nombre   = 'Actualizar Datos';
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorMensaje($debug = false): string
    {
        $this->mensajeError = 'Estimado alumno para brindarte un mejor servicio es necesario que actualices tu información personal.';

        return $this->mensajeError.($debug ? '('.__CLASS__.')' : '');
    }

    /**
     * {@inheritdoc}
     */
    public function getExitoMensaje(): string
    {
        return 'Validación Actualizar Datos superada con éxito.';
    }

    /**
     * {@inheritdoc}
     */
    public function servicioDisponible(): bool
    {
        $this->cicloEscolar = $this->em->getRepository('AragonEscPublico:CicloEscolar')->getCicloActual();
        $alumno             = $this->em->getRepository('AragonEscPublico:Alumno')->find($this->idAlumno);
        $idCiclo            = null;

        try {
            $idCiclo = $this->em->getUnitOfWork()->getSingleIdentifierValue($alumno->getCicloActDatos());
        } catch (Exception $exc) {
            $idCiclo = null;
        }

        if (!empty($this->cicloEscolar)) {
            if ($this->cicloEscolar->getId() == $idCiclo) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }
}
