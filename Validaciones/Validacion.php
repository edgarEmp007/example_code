<?php

namespace Aragon\Escolares\Servicios\Validaciones;

/**
 * Clase abstracta Validacion.
 *
 * @category    Validaciones
 *
 * @author      Edgar Macias<edgaremp007@gmail.com>
 * @copyright   Copyright (c) 2020  (https://www.acceso.aragon.unam.mx/)
 */
abstract class Validacion
{
    protected $em;

    /**
     * Propiedad que indica el mensaje de error en caso de que la regla no paso con éxito.
     *
     * @var bool
     */
    protected $mensajeError;

    /**
     * Propiedad que indica el nombre de la regla.
     *
     * @var string
     */
    protected $nombre;

    /**
     * Método donde se hacen las consultas necesarias y validaciones para
     * obtener un array de grupos ya filtrados.
     */
    abstract public function servicioDisponible(): bool;

    /**
     * Método que obtiene un mensaje si la validación paso con éxito.
     */
    abstract public function getExitoMensaje(): string;

    /**
     * Método que obtiene un mensaje de error si la validación no paso.
     *
     * @param bool $debug Variable que me indica si en el mensaje de error se coloca el nombre de
     *                    la clase donde se genero el error.
     */
    abstract public function getErrorMensaje($debug = false): string;

    public function getNombre(): string
    {
        return $this->nombre;
    }
}
