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
*  @author    fjrus SA <fjrus@webimpacto.es>
*  @copyright 2007-2019 fjrus SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once('classes/Cambiar.php');
require_once('classes/Original.php');

class CambiarPrecio extends Module
{
    protected $config_form = false;
    
    public function __construct()
    {
        $this->name = 'cambiarprecio';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'fjrus';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('aumenta o disminuye');
        $this->description = $this->l('aumentar o disminuir el precio de los productos');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        include(dirname(__FILE__).'\sql\install.php');
        $this->getPrecioOriginal();
        $this->registerHook('header');
        $this->registerHook('backOfficeHeader');
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        $this->html = "";
        $preciosOrigen = $this->getOriginalprice();
        if (Tools::isSubmit('savecambiarprecio')) {
            if ($this->processSave()) {
                $contador=0;
                $datos = $this->getDatos();
                foreach ($datos as $dato) {
                    $nuevoDato = new Cambiar($dato['id_cambioprecio']);///nuevoDato
                    if ($contador < count($datos)-1) {
                        $nuevoDato->actual = false;
                    } else {
                        $nuevoDato->actual = true;
                        foreach ($preciosOrigen as $precioOrigen) {
                            $precio = new Product($precioOrigen['id_product']);
                            if ($nuevoDato->tipo == "porcentaje" && $nuevoDato->opcion == "disminuir") {
                                $precio->price = $precioOrigen['originalprice'] - ($precioOrigen['originalprice'] * $nuevoDato->cantidad/100);
                            } elseif ($nuevoDato->tipo == "cantidad" && $nuevoDato->opcion == "disminuir") {
                                $precio->price = $precioOrigen['originalprice'] - $nuevoDato->cantidad;
                            } elseif ($nuevoDato->tipo == "porcentaje" && $nuevoDato->opcion == "aumentar") {
                                $precio->price = $precioOrigen['originalprice'] + ($precioOrigen['originalprice'] * $nuevoDato->cantidad/100);
                            } elseif ($nuevoDato->tipo == "cantidad" && $nuevoDato->opcion == "aumentar") {
                                $precio->price = $precioOrigen['originalprice'] + $nuevoDato->cantidad;
                            }
                            if ($precio->price >= $precio->wholesale_price) {
                                    $precio->save();
                            }
                        }
                    }
                    $contador++;
                    $nuevoDato->save();
                }
                $this->html .= $this->render();
                $this->html .= $this->renderForm();
                $this->html .= $this->renderList();
                return $this->html;
            } else {
                $this->html .= $this->render();
                $this->html .= $this->renderForm();
                $this->html .= $this->renderList();
                return $this->html;
            }
        } elseif (Tools::isSubmit('updatecambiarprecio')) {
            $datos = $this->getDatos();
            $idDato = Tools::getValue('id_cambioprecio');
            $this->getPreciosnuevos();
            foreach ($datos as $dato) {
                $nuevoDato = new Cambiar($dato['id_cambioprecio']);
                if ($nuevoDato->id_cambioprecio != $idDato) {
                    $nuevoDato->actual = false;
                    $nuevoDato->save();
                } else {
                    $nuevoDato->actual = true;
                    $nuevoDato->save();
                }
            }
            $this->html .= $this->render();
            $this->html .= $this->renderForm();
            $this->html .= $this->renderList();
            return $this->html;
        } else {
            $this->html .= $this->render();
            $this->html .= $this->renderForm();
            $this->html .= $this->renderList();
            return $this->html;
        }
        $contador++;
    }
   
    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $fields_form = array(
        'tinymce' => true,
        'legend' => array(
        'title' => $this->l('Formulario opciones'),
        ),
        'input' => array(
            
                array(
                    'type' => 'select',
                    'label' => $this->l('Tipo'),
                    'name' => 'tipo',
                    'options' => array(
                        'query' => array(
                            array(
                                'id_option' => 1,
                                'name' => 'porcentaje',
                            ),
                            array(
                                'id_option' => 2,
                                'name' => 'cantidad',
                            ),
                        ),
                    'id' => 'id_option',
                    'name' => 'name',
                    )
                ),
                array(
                    'type' => 'select',
                    'label' => $this->l('Opcion'),
                    'name' => 'opcion',
                    'options' => array(
                        'query' => array(
                            array(
                                'id_option' => 1,
                                'name' => 'aumentar',
                            ),
                            array(
                                'id_option' => 2,
                                'name' => 'disminuir',
                            ),
                        ),
                    'id' => 'id_option',
                    'name' => 'name',
                    )
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Cantidad '),
                    'name' => 'cantidad',
                    'col' => '2'
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
            'buttons' => array(
                array(
                    'href' => AdminController::$currentIndex.'&configure='.$this->name.'&token='.
                    Tools::getAdminTokenLite('AdminModules'),
                    'title' => $this->l('Back to list'),
                    'icon' => 'process-icon-back'
                )
            )
        );

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->identifier = $this->identifier;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->toolbar_scroll = false;
        $helper->title = $this->displayName;
        $helper->submit_action = 'savecambiarprecio';
        return $helper->generateForm(array(array('form' => $fields_form)));
    }

    protected function renderList()
    {
            $this->fields_list = array();
            $this->fields_list['fecha_cambio'] = array(
                'title' => $this->l('fecha_cambio'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
            );
            $this->fields_list['tipo'] = array(
                'title' => $this->l('Tipo'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
                'remove_onclick' => true,
            );
            $this->fields_list['opcion'] = array(
                'title' => $this->l('Opcion'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
                'remove_onclick' => true,
            );
            $this->fields_list['cantidad'] = array(
                'title' => $this->l('cantidad'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
                'remove_onclick' => true,
            );
            $this->fields_list['actual'] = array(
                'title' => $this->l('Actual'),
                'type' => 'text',
                'search' => false,
                'orderby' => false,
                'remove_onclick' => true,
            );
            
            $helper = new HelperList();
            $helper->shopLinkType = '';
            $helper->simple_header = false;
            $helper->identifier = 'id_cambioprecio';
            $helper->show_toolbar = false;
            $helper->imageType = 'jpg';
            $helper->actions = array('edit');
            $helper->title = $this->displayName;
            $helper->table = $this->name;
            $helper->token = Tools::getAdminTokenLite('AdminModules');
            $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
            $content = $this->getListContent($this->context->language->id);
            return $helper->generateList($content, $this->fields_list);
    }
    /**
     * Create the structure of your form.
     */

    protected function render()
    {
        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    protected function getListContent()
    {
        $registros = $this->getDatos();
        return $registros;
    }
    
    public function getDatos($ordenar = false, $id = 0)
    {
        $sql ='SELECT `id_cambioprecio`, `fecha_cambio`,`tipo`, `opcion`, `cantidad`, `actual`
        FROM `'._DB_PREFIX_.'cambiarprecio`';
    
        if ($ordenar) {
            $sql .= 'WHERE id_cambioprecio >= ' . $id  .' ORDER BY `fecha_cambio` DESC';
        }
        return Db::getInstance()->executeS($sql);
    }

    public function getPrecioproducto()
    {
        $sql = 'SELECT `id_product`, `price`,`wholesale_price`
            FROM `'._DB_PREFIX_.'product` ';
            
        return Db::getInstance()->ExecuteS($sql);
    }


    public function getOriginalprice()
    {
        $sql = 'SELECT `id_registro`, `id_product`,`originalprice`
        FROM `'._DB_PREFIX_.'originalprecio`';

        return Db::getInstance()->ExecuteS($sql);
    }

    public function getPrecioOriginal()
    {
        $datos = $this->getPrecioproducto();
        foreach ($datos as $dato) {
            $precio = new Product($dato['id_product']);
            $precioOrigen = new Original();
            $precioOrigen->id_product = $dato['id_product'];
            $precioOrigen->originalprice = $precio->price;
            $precioOrigen->save();
        }
    }

    public function processSave()
    {
        $this->getPreciosnuevos();

        $saved = false;
        $fecha = date("d/m/Y H:i:s");
        
        if (isset($_REQUEST['savecambiarprecio'])) {
            $dato = new Cambiar();
            $dato->fecha_cambio = $fecha;
            $dato->tipo = Tools::getValue('tipo') == 1 ? "porcentaje" : "cantidad";
            $dato->opcion = Tools::getValue('opcion') == 1 ? "aumentar" : "disminuir";
            $dato->cantidad = Tools::getValue('cantidad');
            $dato->actual = true;
            $saved = $dato->save();
        }
        return $saved;
    }

    public function getPreciosnuevos()
    {
        $preciosOrigen = $this->getOriginalprice();
        
        $nuevoDato = new Cambiar(Tools::getValue('id_cambioprecio'));
            
        foreach ($preciosOrigen as $precioOrigen) {
            $precio = new Product($precioOrigen['id_product']);
            if ($nuevoDato->tipo == "porcentaje" && $nuevoDato->opcion == "disminuir") {
                $precio->price=$precioOrigen['originalprice']-($precioOrigen['originalprice']*$nuevoDato->cantidad/100);
            } elseif ($nuevoDato->tipo == "cantidad" && $nuevoDato->opcion == "disminuir") {
                $precio->price = $precioOrigen['originalprice'] - $nuevoDato->cantidad;
            } elseif ($nuevoDato->tipo == "porcentaje" && $nuevoDato->opcion == "aumentar") {
                $precio->price=$precioOrigen['originalprice']+($precioOrigen['originalprice']*$nuevoDato->cantidad/100);
            } elseif ($nuevoDato->tipo == "cantidad" && $nuevoDato->opcion == "aumentar") {
                $precio->price = $precioOrigen['originalprice'] + $nuevoDato->cantidad;
            }
            if ($precio->price >= $precio->wholesale_price) {
                $precio->save();
            }
        }
    }
    /**
     * Save form data.
     */

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }
}
