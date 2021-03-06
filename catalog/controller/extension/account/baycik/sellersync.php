<?php
class ControllerExtensionAccountBaycikSellersync extends Controller{
	private $error = array();
	public function index(){
		if (!$this->customer->isLogged()) {
			$this->session->data['redirect'] = $this->url->link('extension/account/baycik/sellersync', '', true);

			$this->response->redirect($this->url->link('account/login', '', true));
		}
		$store_detail = $this->customer->isSeller();
		if(!isset($store_detail['store_status'])){
			$this->response->redirect($this->url->link('account/account', '', true));
		}
		$this->load->language('baycik/sellersync');
		
		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('extension/baycik/sellersync');
		
                $data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home','',true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/account/baycik/sellersync',  $url, true)
		);
                $data['heading_title'] =  $this->language->get('heading_title');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');	
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
                $data['seller_id'] = $store_detail['id'];
                $data['categories'] =  $categories = $this->model_extension_baycik_sellersync->check_get_cat_list();
                $data['destination_categories'] = $this->getDestCategories();
		//$this->getList();
                $this->parse_happywear(1);
                $this->response->setOutput($this->load->view('account/baycik/sellersync', $data));
            
	}    
        
        public function parse_happywear ($sync_id){
            $tmpfile = tempnam("/tmp", "tmp_");
            copy("W:/price-list.csv", $tmpfile);
            $this->load->model('extension/baycik/sellersync');
            $this->model_extension_baycik_sellersync->parse_happywear($sync_id, addslashes($tmpfile)); 
}
        
        public function getDestCategories (){
            $list = $this->config->get('module_purpletree_multivendor_allow_category');
            $new_list = [];
            $keys = array_keys($list);
            $values = array_values($list);
            for($i=0; $i<count($list); $i++){
                $new_key = array(
                    'category_path'=>$keys[$i],
                    'category_id'=>$values[$i]
                );
                array_push($new_list, $new_key);
            }
            return $new_list;  
        }
        
        public function importUserData (){
            $this->load->model('extension/baycik/sellersync');
            $data = $this->request->post['data'];
            
            $seller_id = (int) $this->request->post['seller_id'];
            $decoded_text = html_entity_decode($data);
            $import_array = json_decode($decoded_text, true);
            foreach($import_array as $item){
                $this->model_extension_baycik_sellersync->importCategories(json_decode(json_encode($item)), $seller_id);
            }
            
        }
        
        private $data = array(
            "category_lvl1"=>"Женщинам",
            "category_lvl2"=>"Нижнее белье",
            "category_lvl3"=>"Мини-шортики 7шт.",
            "category_comission"=>"5",
            "destination_category"=>"27"
            );
        public function testImport() {
             $this->load->model('extension/baycik/sellersync');
             $this->model_extension_baycik_sellersync->importCategories(json_decode(json_encode($this->data)));
        }
        
        
        public function parse2 (){
            if (($handle = fopen("https://happywear.ru/exchange/xml/price-list.csv", "r")) !== FALSE) {
                header('Content-Type: text/html; charset=UTF-8');
                $sync_id = $this->request->get['sync_id'];
                $this->load->model('extension/baycik/sellersync');
                $i=0;
                while (($data = fgetcsv($handle, 5000, ";")) !== FALSE && $i++<1000) {
                    $row=[
                        'category_lvl1'=> addslashes($data[0]),
                        'category_lvl2'=>addslashes($data[1]),
                        'model'=> addslashes($data[2]),
                        'category_lvl3'=>addslashes($data[3]),
                        'filter1'=>addslashes($data[4]),
                        'filter2'=>addslashes($data[5]),
                        'manufacturer'=>addslashes($data[6]),
                        'origin_country'=>addslashes($data[7]),
                        'option1'=>addslashes($data[8]),
                        'url'=>addslashes($data[9]),
                        'img'=>addslashes($data[10]),
                        'description'=>addslashes($data[11]),
                        'price1'=>addslashes($data[12]),
                        'price2'=>addslashes($data[16]),
                        'price3'=>addslashes($data[17]),
                        'min_order'=>addslashes($data[14])
                    ];
                    if( $row['model'] ){
                        $this->model_extension_baycik_sellersync->insert_parsed_row($sync_id, $row); 
                    }

                }
                fclose($handle);
            }
        } 
        
        
}