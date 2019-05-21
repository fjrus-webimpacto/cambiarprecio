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
require_once('classes/Excluido.php');

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

		$this->displayName = $this->l('cambiarprecio');
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
		return parent::install() &&
			$this->registerHook('header') &&
			$this->registerHook('backOfficeHeader');
	}

	public function uninstall()
	{
		return parent::uninstall();
	}

	public function getContent()
	{      
		$this->html = "";
		
		if (Tools::isSubmit('savecambiarprecio')) {
			if ($this->processSave()) {
				$contador=0;
				$datos = $this->getRegistros();
				foreach ($datos as $dato) {
					$nuevoDato = new Cambiar($dato['id_precionuevo']);
					
					if ($contador < count($datos)-1) {
						$nuevoDato->restaurado = false;
						$nuevoDato->actual = false;
					} else {
						$productos = $this->getPrecioproducto();
						//ddd($products);
						$nuevoDato->restaurado = false;
						$nuevoDato->actual = true;
						foreach ($productos as $producto) {
							$prod = new Product($producto['id_product']);
							//ddd($prod);
							if ($nuevoDato->tipo == "porcentaje" && $nuevoDato->opcion == "disminuir") {
								 $prod->price =  $prod->price - ($prod->price * $nuevoDato->cantidad/100);
							} elseif ($nuevoDato->tipo == "cantidad" && $nuevoDato->opcion == "disminuir") {
								$prod->price =  $prod->price - $nuevoDato->cantidad;
							} elseif ($nuevoDato->tipo == "porcentaje" && $nuevoDato->opcion == "aumentar") {
								$prod->price =  $prod->price + ($prod->price * $nuevoDato->cantidad/100);
							} elseif ($nuevoDato->tipo == "cantidad" && $nuevoDato->opcion == "aumentar") {
								$prod->price = $prod->price + $nuevoDato->cantidad;
							}

							if ($prod->price >= $prod->wholesale_price) {
								$prod->save();
							   // ddd($prod);
							}
							else{
								$exclu = new Excluido();
								$exclu->id_precionuevo = $nuevoDato->$id_precionuevo; 
								//ddd($prod);
								$exclu->id_producto = $prod->id;
								$exclu->save();
							}                     
						}
					}
				   // ddd($contador);
					$contador++;
					
					$nuevoDato->save();
				}
				 
				$this->html .= $this->renderForm();
				$this->html .= $this->renderList();
				return $this->html;
			} else {
				//ddd(33);
				$this->html .= $this->renderForm();
				$this->html .= $this->renderList();
				return $this->html;
			}
		} elseif (Tools::isSubmit('updatecambiarprecio')) {
			$datos = $this->getRegistros();
			$idDato = Tools::getValue('id_precionuevo');
			$this->getPreciosnuevos();
			foreach ($datos as $dato) {
				$nuevoDato = new Cambiar($dato['id_precionuevo']);
				if ($nuevoDato->id_precionuevo < $idDato-1) {
					$nuevoDato->restaurado = false;
					$nuevoDato->actual = false;
					$nuevoDato->save();
				} elseif ($nuevoDato->id_precionuevo == $idDato-1) {
					$nuevoDato->restaurado = false;
					$nuevoDato->actual = true;
					$nuevoDato->save();
				} else {
					$nuevoDato->restaurado = true;
					$nuevoDato->actual = false;
					$nuevoDato->save();
				}
			}
			
			$this->html .= $this->renderForm();
			$this->html .= $this->renderList();
			return $this->html;
		} else {
			$this->html .= $this->renderForm();
			$this->html .= $this->renderList();
			return $this->html;
		}
	}
	
	public function registrosExcluidos($id_regis,$id_prod)
	{
		$sql = 'SELECT `id_precionuevo`, `id_excluido`
		FROM `'._DB_PREFIX_.'excluido`
		WHERE `id_precionuevo` = '.(int)$id_regis.' AND  `id_excluido` = "'.$id_prod.'"';

		if(empty(Db::getInstance()->ExecuteS($sql))) {
			return false;
		}
		else {
			return true;
		}
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
		$fields_value = array();
		$helper = new HelperForm();
		$helper->module = $this;
		$helper->identifier = $this->identifier;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->toolbar_scroll = false;
		$helper->title = $this->displayName;
		$helper->submit_action = 'savecambiarprecio';
		
		$fields_value['opcion']="";
		$fields_value['tipo']="";
		$fields_value['cantidad']="";
		$helper->fields_value = $fields_value;
		
		
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
			$this->fields_list['restaurado'] = array(
				'title' => $this->l('Restaurado'),
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
			$helper->identifier = 'id_precionuevo';
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
/*
	protected function getListContent($orden = false, $id=0)
	{
		$sql = 'SELECT `id_precionuevo`, `fecha_cambio`,`tipo`, `opcion`, `cantidad`, `restaurado`,`actual`
		FROM `'._DB_PREFIX_.'cambiarprecio`';
	
			if ($orden) {
				$sql .=  ' WHERE id_precionuevo >= ' . $id  .' ORDER BY `fecha_cambio` DESC';
			}
	
		return Db::getInstance()->executeS($sql);
	}
	*/
	protected function getListContent()
	{ 
		$registros = $this->getRegistros();
		return $registros;
	}
	
	public function getRegistros($orden = false, $id = 0)
	{
	 
		$sql = 'SELECT `id_precionuevo`, `fecha_cambio`,`tipo`, `opcion`, `cantidad`, `restaurado`,`actual`
		FROM `'._DB_PREFIX_.'cambiarprecio`';
	
			if ($orden) {
				$sql .=  ' WHERE id_precionuevo >= ' . $id  .' ORDER BY `fecha_cambio` DESC';
			}
	
		return Db::getInstance()->executeS($sql);
	}


	public function getPrecioproducto()
	{
		$sql = 'SELECT `id_product`, `price`,`wholesale_price`
			FROM `'._DB_PREFIX_.'product` ';
			
		return Db::getInstance()->ExecuteS($sql);
	}

	public function getFormValues()
	{
		$fields_value = array();
		$id_precionuevo = (int)Tools::getValue('id_precionuevo');
		if ($id_precionuevo) {
			$dato = new Cambiar((int)$id_precionuevo);
			$fields_value['id_precionuevo'] = $dato->id_precionuevo;
			$fields_value['tipo'] = $dato->tipo;
			$fields_value['opcion'] = $dato->opcion;
		} else {
			$fields_value['id_precionuevo'] = "";
			$fields_value['tipo'] = "";
			$fields_value['opcion'] = "";
		}
		$fields_value['id_precionuevo'] = $id_precionuevo;
		
		return $fields_value;
	}


	public function processSave()
	{
		$this->getPreciosnuevos();
		$saved = false;
		$fecha = date("d/m/Y H:i:s");
		if (isset($_REQUEST['savecambiarprecio'])) {
			$dato = new Cambiar();
			$dato->fecha_cambio = $fecha;
			if (Tools::getValue('tipo') == 1) {
				$dato->tipo = "porcentaje";
			} else {
				$dato->tipo = "cantidad";
			}
			if (Tools::getValue('opcion') == 1) {
				$dato->opcion = "aumentar";
			} else {
				$dato->opcion = "disminuir";
			}
			$dato->cantidad = Tools::getValue('cantidad');
			$dato->restaurado = false;
			$dato->actual = true;
			$saved = $dato->save();
		} else {
			$dato = new Cambiar();
			$dato->fecha_cambio = $fecha;
			if (Tools::getValue('tipo') == 1) {
				$dato->tipo = "porcentaje";
			} else {
				$dato->tipo = "cantidad";
			}
			if (Tools::getValue('opcion') == 1) {
				$dato->opcion = "aumentar";
			} else {
				$dato->opcion = "disminuir";
			}
			$dato->cantidad = Tools::getValue('cantidad');
			$dato->restaurado = false;
			$dato->actual = true;
			$saved = $dato->save();
		}
	
		return $saved;
	}
	

	public function getPreciosnuevos()
	{
 
		$productos = $this->getPrecioproducto();
		
		if (Tools::getValue('id_precionuevo') >= 1) {
			$datos = $this->getRegistros(true, Tools::getValue('id_precionuevo'));
		 //   ddd($datos);
			foreach ($datos as $dato) {
				$nuevoDato2 = new Cambiar($dato['id_precionuevo']);
				foreach ($productos as $producto) {
					$prod = new Product($producto['id_product']);
					if (!$this->registrosExcluidos($nuevoDato2->id_precionuevo, $prod->id)) {
						if ($nuevoDato2->tipo == "porcentaje" && $nuevoDato2->opcion == "disminuir") {
							$prod->price =  $prod->price + ($prod->price * $nuevoDato2->cantidad/100);
						} elseif ($nuevoDato2->tipo == "cantidad" && $nuevoDato2->opcion == "disminuir") {
							$prod->price =  $prod->price + $nuevoDato2->cantidad;
						} elseif ($nuevoDato2->tipo == "porcentaje" && $nuevoDato2->opcion == "aumentar") {
							$prod->price =  $prod->price - ($prod->price * $nuevoDato2->cantidad/100);
						} elseif ($nuevoDato2->tipo == "cantidad" && $nuevoDato2->opcion == "aumentar") {
							$prod->price = $prod->price - $nuevoDato2->cantidad;
						}
					$prod->save();
					}
				}
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
