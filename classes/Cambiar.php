<?php
/**
* 2007-2019 fjrus
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author FJRUS SA <fjrus@webimpacto.es>
*  @copyright  2007-2019 fjrus SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

class Cambiar extends ObjectModel
{
    public $id_precionuevo;
    public $fecha_cambio;
    public $precio;
    public $tipo;
    public $opcion;
    public $cantidad;
    public $restaurado;
    public $actual;
    

    public static $definition = array
    (
        'table' => 'cambiarprecio',
        'primary' => 'id_precionuevo',
        'fields' => array(
            'fecha_cambio' => array('type' => self::TYPE_DATE),
            'tipo' => array('type' => self::TYPE_STRING,  'size' => 255),
            'opcion' => array('type' => self::TYPE_STRING,  'size' => 255),
            'cantidad' => array('type' => self::TYPE_FLOAT,  'copy_post' => false),
            'restaurado' => array('type' => self::TYPE_BOOL),
            'actual' => array('type' => self::TYPE_BOOL),
        ),
    );
}

