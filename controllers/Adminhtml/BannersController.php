<?php

class Cammino_Banners_Adminhtml_BannersController extends Mage_Adminhtml_Controller_action
{

	protected function _initAction() {
		$this->loadLayout()
			->_setActiveMenu('banners/items')
			->_addBreadcrumb(Mage::helper('banners')->__('Items Manager'), Mage::helper('banners')->__('Item Manager'));
		
		$this->getLayout()->getBlock('head')->addCss('cammino_banners.css');
		
		return $this;
	}
 
	public function indexAction() {
		$this->_initAction()->renderLayout();
	}

	public function editAction() {
		$id     = $this->getRequest()->getParam('id');
		$model  = Mage::getModel('banners/banners')->load($id);

		if ($model->getId() || $id == 0) {
			$data = Mage::getSingleton('adminhtml/session')->getFormData(true);
			if (!empty($data)) {
				$model->setData($data);
			}

			Mage::register('banners_data', $model);

			$this->loadLayout();
			
			$this->_setActiveMenu('banners/items');

			$this->_addBreadcrumb(Mage::helper('banners')->__('Item Manager'), Mage::helper('banners')->__('Item Manager'));
			$this->_addBreadcrumb(Mage::helper('banners')->__('Item News'), Mage::helper('banners')->__('Item News'));

			$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
			$this->getLayout()->getBlock('head')->addCss('cammino_banners.css');
			
			$this->_addContent($this->getLayout()->createBlock('banners/adminhtml_banners_edit'))
				->_addLeft($this->getLayout()->createBlock('banners/adminhtml_banners_edit_tabs'));

			$this->renderLayout();
			
		} else {
			Mage::getSingleton('adminhtml/session')->addError(Mage::helper('banners')->__('Item does not exist'));
			$this->_redirect('*/*/');
		}
	}
 
	public function newAction() {
		$this->_forward('edit');
	}
 
 	// Save event
	public function saveAction() {
		if ($data = $this->getRequest()->getPost()) {
			
			// Date (Timestamp)
			$now = Mage::getModel('core/date')->timestamp(time());

			// Save default image
			if(isset($_FILES['filename']['name']) && $_FILES['filename']['name'] != '') {	
				try {
					$uploader = new Varien_File_Uploader('filename');
	           		$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
					$uploader->setAllowRenameFiles(false);
					$uploader->setFilesDispersion(false);

					$path = Mage::getBaseDir('media') . DS . "banners";

					// Check if this directory exists, if is negative the directory is created.
					if (!is_dir($path)) {
						mkdir($path);
					}

					// Capturing the image's extension and url
					// Removing all special characters and blank spaces
					// And including time stamp to file name
					$ext = '.' . end(explode('.', $_FILES['filename']['name']));
					$baseFilename = str_replace($ext, '', $_FILES['filename']['name']) . '-' . $now;
					$filename = Mage::getModel('catalog/product_url')->formatUrlKey($baseFilename) . $ext;
		            $uploader->save($path, $filename);
		            $data['filename'] = $filename;

					// $uploader->save($path, $_FILES['filename']['name']);

				} catch (Exception $e) {}

	  			// $data['filename'] = $_FILES['filename']['name'];
			}

			// Save responsive image
			if(isset($_FILES['filename_responsive']['name']) && $_FILES['filename_responsive']['name'] != '') {
				try {
					$uploader = new Varien_File_Uploader('filename_responsive');
	           		$uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
					$uploader->setAllowRenameFiles(false);
					$uploader->setFilesDispersion(false);

					$path = Mage::getBaseDir('media') . DS . "banners" ;
					
					mkdir($path);
					
					// Capturing the image's extension and url
					// Removing all special characters and blank spaces
					// And including time stamp to file name
					$ext = '.' . end(explode('.', $_FILES['filename_responsive']['name']));
					$baseFilename = str_replace($ext, '', $_FILES['filename_responsive']['name']) . '-' . $now;
					$filename = Mage::getModel('catalog/product_url')->formatUrlKey($baseFilename) . $ext;
		            $uploader->save($path, $filename);
		            $data['filename_responsive'] = $filename;

					// $uploader->save($path, $_FILES['filename_responsive']['name']);
					
				} catch (Exception $e) {}
	        
	  			// $data['filename_responsive'] = $_FILES['filename_responsive']['name'];
			}

			$data = $this->_filterDates($data, array('start_at', 'end_at'));
			
			// FIX BUG VERSION 1.5
			// Ao mandar uma variavel vazia estava salvando o valor no banco de 0000-00-00 00:00:00, entao caso for vazio forçamos o valor NULL.
			$data['start_at'] = empty($data['start_at']) ? NULL : $data['start_at'];
			$data['end_at']   = empty($data['end_at']) ? NULL : $data['end_at'];

			$model = Mage::getModel('banners/banners');		
			$model->setData($data)
				->setId($this->getRequest()->getParam('id'));
			
			if (!empty($data['start_at'])) {
				$date = Mage::app()->getLocale()->date($data['start_at'], Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
				$model->setStartAtDate($date->toString('YYYY-MM-dd HH:mm:ss'));
			}
			if (!empty($data['end_at'])) {
				$date = Mage::app()->getLocale()->date($data['end_at'], Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM);
				$model->setEndAtDate($date->toString('YYYY-MM-dd HH:mm:ss'));
			}
			
			try {
				if ($model->getCreatedTime == NULL || $model->getUpdateTime() == NULL) {
					$model->setCreatedTime(now())
						->setUpdateTime(now());
				} else {
					$model->setUpdateTime(now());
				}	
				
				$model->save();
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('banners')->__('Item was successfully saved'));
				Mage::getSingleton('adminhtml/session')->setFormData(false);

				if ($this->getRequest()->getParam('back')) {
					$this->_redirect('*/*/edit', array('id' => $model->getId()));
					return;
				}
				$this->_redirect('*/*/');
				return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('banners')->__('Unable to find item to save'));
        $this->_redirect('*/*/');
	}
 
	public function deleteAction() {
		if( $this->getRequest()->getParam('id') > 0 ) {
			try {
				$model = Mage::getModel('banners/banners');
				 
				$model->setId($this->getRequest()->getParam('id'))
					->delete();
					 
				Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('banners')->__('Item was successfully deleted'));
				$this->_redirect('*/*/');
			} catch (Exception $e) {
				Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
				$this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
			}
		}
		$this->_redirect('*/*/');
	}

    protected function _sendUploadResponse($fileName, $content, $contentType='application/octet-stream')
    {
        $response = $this->getResponse();
        $response->setHeader('HTTP/1.1 200 OK','');
        $response->setHeader('Pragma', 'public', true);
        $response->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true);
        $response->setHeader('Content-Disposition', 'attachment; filename='.$fileName);
        $response->setHeader('Last-Modified', date('r'));
        $response->setHeader('Accept-Ranges', 'bytes');
        $response->setHeader('Content-Length', strlen($content));
        $response->setHeader('Content-type', $contentType);
        $response->setBody($content);
        $response->sendResponse();
        die;
    }
}