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
*  @author    FJRUS SA <fjrus@webimpacto.es>
*  @copyright 2007-2019 fjrus SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cambiarprecio` (
    `id_precionuevo` int(11) NOT NULL AUTO_INCREMENT,
    `fecha_cambio` varchar(30) NOT NULL,
    `tipo` varchar(15) NOT NULL,
    `opcion`varchar(15) NOT NULL,
    `cantidad` int(11) NOT NULL,
    `restaurado` boolean NOT NULL,
    `actual` boolean NOT NULL,
      PRIMARY KEY  (`id_precionuevo`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'excluido` (
    `id_tabla` int(11) NOT NULL AUTO_INCREMENT,
    `id_producto` int(11) NOT NULL,
    `id_excluido` int(11) NOT NULL,
    PRIMARY KEY  (`id_tabla`)
    ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';


foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
