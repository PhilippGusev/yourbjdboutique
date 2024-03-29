<?php

class ControllerCheckoutOnepagecheckout extends Controller
{
    public $errors = array();

    public function index()
    {
        // Validate cart has products and has stock.
        if ((!$this->cart->hasProducts() && empty($this->session->data['vouchers'])) || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout'))) {
            $this->response->redirect($this->url->link('checkout/cart'));
        }
        $this->document->addStyle('catalog/view/theme/default/stylesheet/onepagecheckout.css');

        // Validate minimum quantity requirements.
        $products = $this->cart->getProducts();
        $this->load->language('checkout/onepagecheckout');
        $data['text_customer'] = $this->language->get('text_customer');
        $data['text_full_name'] = $this->language->get('text_full_name');
        $data['text_telephone'] = $this->language->get('text_telephone');
        $data['text_email'] = $this->language->get('text_email');
        $data['text_town'] = $this->language->get('text_town');
        $data['text_delivery_method'] = $this->language->get('text_delivery_method');
        $data['text_delivery_type_1'] = $this->language->get('text_delivery_type_1');
        $data['text_delivery_type_2'] = $this->language->get('text_delivery_type_2');
        $data['text_delivery_placeholder'] = $this->language->get('text_delivery_placeholder');
        $data['text_payment_method'] = $this->language->get('text_payment_method');
        $data['text_comment'] = $this->language->get('text_comment');
        $data['text_confirm'] = $this->language->get('text_confirm');
        $data['text_product'] = $this->language->get('text_product');
        $data['text_price'] = $this->language->get('text_price');
        $data['text_quantity'] = $this->language->get('text_quantity');
        $data['text_total'] = $this->language->get('text_total');
        $data['cart_total'] = 0;
        foreach ($products as $i => $product) {
            $products[$i]['price'] = $this->currency->format($product['price'], $this->session->data['currency']);
            $product_total = 0;
            $data['cart_total'] += $product['total'];
            $option_data = array();

            foreach ($product['option'] as $option) {
                $option_data[] = array(
                    'product_option_id' => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                    'option_id' => $option['option_id'],
                    'option_value_id' => $option['option_value_id'],
                    'name' => $option['name'],
                    'value' => $option['value'],
                    'type' => $option['type']
                );
            }
            foreach ($products as $product_2) {
                if ($product_2['product_id'] == $product['product_id']) {
                    $product_total += $product_2['quantity'];
                }
            }

            if ($product['minimum'] > $product_total) {
                $this->response->redirect($this->url->link('checkout/cart'));
            }
        }


        $data['products'] = $products;
        $total_val = $data['cart_total'];
        $data['cart_total'] = $this->currency->format($data['cart_total'], $this->session->data['currency']);

        // Gift Voucher
        $data['vouchers'] = array();

        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $data['vouchers'][] = array(
                    'description' => $voucher['description'],
                    'code' => token(10),
                    'to_name' => $voucher['to_name'],
                    'to_email' => $voucher['to_email'],
                    'from_name' => $voucher['from_name'],
                    'from_email' => $voucher['from_email'],
                    'voucher_theme_id' => $voucher['voucher_theme_id'],
                    'message' => $voucher['message'],
                    'amount' => $voucher['amount']
                );
            }
        }


        $this->load->language('checkout/checkout');
        $data['entry_firstname'] = $this->language->get('entry_firstname');
        $data['entry_lastname'] = $this->language->get('entry_lastname');
        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_cart'),
            'href' => $this->url->link('checkout/cart')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('checkout/checkout', '', true)
        );

        $data['heading_title'] = $this->language->get('heading_title');


        if (isset($this->session->data['error'])) {
            $data['error_warning'] = $this->session->data['error'];
            unset($this->session->data['error']);
        } else {
            $data['error_warning'] = '';
        }

        $data['logged'] = $this->customer->isLogged();

        if (isset($this->session->data['account'])) {
            $data['account'] = $this->session->data['account'];
        } else {
            $data['account'] = '';
        }
        if (isset($this->session->data['payment_address']['firstname'])) {
            $data['firstname'] = $this->session->data['payment_address']['firstname'];
        } else {
            $data['firstname'] = '';
        }


        if (isset($this->session->data['payment_address']['address_1'])) {
            $data['address_1'] = $this->session->data['payment_address']['address_1']; //nomer otdelenia ili adres poluchatelya
        } else {
            $data['address_1'] = '';
        }

        if (isset($this->session->data['payment_address']['city'])) {
            $data['city'] = $this->session->data['payment_address']['city'];
        } else {
            $data['city'] = '';
        }
        if (isset($this->session->data['payment_address']['telephone'])) {
            $data['telephone'] = $this->session->data['payment_address']['telephone'];
        } else {
            $data['telephone'] = '';
        }
        if (isset($this->session->data['comment'])) {
            $data['comment'] = $this->session->data['comment'];
        } else {
            $data['comment'] = '';
        }
        if (isset($this->session->data['email'])) {
            $data['email'] = $this->session->data['email'];
        } else {
            $data['email'] = '';
        }

        if (isset($this->session->data['adress_1'])) {
            $data['adress_1'] = $this->session->data['adress_1'];
        } else {
            $data['adress_1'] = '';
        }


        //    var_dump(json_decode('{"type": "page", "id": 1, "color": "#69F"}',true));exit;
        $this->errors = [];
        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

            if ($this->validate_form()) {
                $order_data = array();
                if ($this->affiliate->isLogged()) {
                    $order_data['affiliate_id'] = $this->affiliate->getId();
                } else {
                    $order_data['affiliate_id'] = '';


                }
                $order_data['invoice_prefix'] = $this->config->get('config_invoice_prefix');
                $order_data['store_id'] = $this->config->get('config_store_id');
                $order_data['store_name'] = $this->config->get('config_name');

                if ($order_data['store_id']) {
                    $order_data['store_url'] = $this->config->get('config_url');
                } else {
                    if ($this->request->server['HTTPS']) {
                        $order_data['store_url'] = HTTPS_SERVER;
                    } else {
                        $order_data['store_url'] = HTTP_SERVER;
                    }
                }
                $order_data['products'] = $data['products'];
                $order_data['vouchers'] = $data['vouchers'];
                $order_data['cart_total'] = $total_val;
                if (isset($this->request->post['firstname'])) {
                    $this->session->data['payment_address']['firstname'] = $this->request->post['firstname'];
                    $order_data['firstname'] = $this->request->post['firstname'];
                }
                if (isset($this->request->post['telephone'])) {
                    $this->session->data['payment_address']['telephone'] = $this->request->post['telephone'];
                    $order_data['telephone'] = $this->request->post['telephone'];
                }
                if (isset($this->request->post['email'])) {
                    $this->session->data['payment_address']['email'] = $this->request->post['email'];
                    $order_data['email'] = $this->request->post['email'];
                }
                if (isset($this->request->post['city'])) {
                    $this->session->data['payment_address']['city'] = $this->request->post['city'];
                    $order_data['city'] = $this->request->post['city'];
                }
                if (isset($this->request->post['shipping_method'])) {
                    $this->session->data['shipping_method'] = json_decode(htmlspecialchars_decode($this->request->post['shipping_method']), true);
                    $order_data['shipping_method'] = json_decode(htmlspecialchars_decode($this->request->post['shipping_method']), true);
                }
                if (isset($this->request->post['address_1'])) {
                    $this->session->data['payment_address']['address_1'] = $this->request->post['address_1'];
                    $order_data['address_1'] = $this->request->post['address_1'];
                }
                //   var_dump( json_encode(['title'=>'title','val'=>'val']));exit;


                if (isset($this->request->post['payment_method'])) {
                    $this->session->data['payment_method'] = json_decode(htmlspecialchars_decode($this->request->post['payment_method']), true);
                    $order_data['payment_method'] = json_decode(htmlspecialchars_decode($this->request->post['payment_method']), true);
                }


                if (isset($this->request->post['comment'])) {
                    $this->session->data['comment'] = $this->request->post['comment'];
                    $order_data['comment'] = $this->request->post['comment'];
                }
                if (isset($this->request->post['delivery-type'])) {
                    $this->session->data['delivery-type'] = $this->request->post['delivery-type'];
                    $order_data['address_1'] = $this->request->post['delivery-type'] . ' - ' . $order_data['address_1'];
                }


                $order_data['language_id'] = $this->config->get('config_language_id');
                $order_data['currency_id'] = $this->currency->getId($this->session->data['currency']);
                $order_data['currency_code'] = $this->session->data['currency'];
                $order_data['currency_value'] = $this->currency->getValue($this->session->data['currency']);
                $order_data['ip'] = $this->request->server['REMOTE_ADDR'];

                if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
                    $order_data['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
                } elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
                    $order_data['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
                } else {
                    $order_data['forwarded_ip'] = '';
                }

                if (isset($this->request->server['HTTP_USER_AGENT'])) {
                    $order_data['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
                } else {
                    $order_data['user_agent'] = '';
                }

                if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
                    $order_data['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
                } else {
                    $order_data['accept_language'] = '';
                }

                $order_data['order_status_id'] =0 ;
                $order_data['customer_id'] = 0;
                if (isset($this->session->data['guest']['customer_group_id'])) {
                    $order_data['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
                } else {
                    $order_data['customer_group_id'] = $this->config->get('config_customer_group_id');
                }


                $this->load->model('checkout/onepagecheckout');
                $json['order_id'] = $this->model_checkout_onepagecheckout->addOrder($order_data);
                $this->load->model('checkout/order');
                $this->model_checkout_order->addOrderHistory($json['order_id'], $this->config->get('config_order_status_id'), '', 0, 0);

                $this->session->data['order_id'] = $json['order_id'];
                // $json['order_id']=$this->addOrder($order_data);
                //   var_dump($json['order_id']);exit;


            } else
                $json['error'] = $this->errors;

            $this->response->addHeader('Content-Type: application/json');
            $this->response->setOutput(json_encode($json));
        } else {

            $this->session->data['shipping_address']['country_id'] = 0;
            $this->session->data['shipping_address']['zone_id'] = 0;
            /*get shippings methods*/


            // Shipping Methods
            $method_data = array();

            $this->load->model('extension/extension');

            $results = $this->model_extension_extension->getExtensions('shipping');

            foreach ($results as $result) {
                if ($this->config->get($result['code'] . '_status')) {
                    $this->load->model('extension/shipping/' . $result['code']);

                    $quote = $this->{'model_extension_shipping_' . $result['code']}->getQuote($this->session->data['shipping_address']);

                    if ($quote) {
                        $method_data[$result['code']] = array(
                            'title' => $quote['title'],
                            'quote' => $quote['quote'],
                            'sort_order' => $quote['sort_order'],
                            'error' => $quote['error']
                        );
                    }
                }
            }

            $sort_order = array();

            foreach ($method_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $method_data);

            $this->session->data['shipping_methods'] = $method_data;


            foreach ($method_data as $i => $shipping_method)
                foreach ($shipping_method['quote'] as $shipping_method2) {
                    $data['shippig_methods'][$i]['value'] = $shipping_method2['code'];
                    $data['shippig_methods'][$i]['title'] = $shipping_method2['title'];
                    if (isset($shipping_method2['cost']))
                        $data['shippig_methods'][$i]['cost'] = $shipping_method2['cost'];
                }
//var_dump( $data['shippig_methods']);exit;


            /* payment methods*/
// Payment Methods
            // Totals
            $totals = array();
            $taxes = $this->cart->getTaxes();
            $total = 0;

            // Because __call can not keep var references so we put them into an array.
            $total_data = array(
                'totals' => &$totals,
                'taxes' => &$taxes,
                'total' => &$total
            );

            $this->load->model('extension/extension');

            $sort_order = array();

            $results = $this->model_extension_extension->getExtensions('total');

            foreach ($results as $key => $value) {
                $sort_order[$key] = $this->config->get($value['code'] . '_sort_order');
            }

            array_multisort($sort_order, SORT_ASC, $results);

            foreach ($results as $result) {
                if ($this->config->get($result['code'] . '_status')) {
                    $this->load->model('extension/total/' . $result['code']);

                    // We have to put the totals in an array so that they pass by reference.
                    $this->{'model_extension_total_' . $result['code']}->getTotal($total_data);
                }
            }

            $method_data = array();
            $this->session->data['payment_address']['country_id'] = 0;
            $this->session->data['payment_address']['zone_id'] = 0;
            $this->load->model('extension/extension');

            $results = $this->model_extension_extension->getExtensions('payment');

            $recurring = $this->cart->hasRecurringProducts();

            foreach ($results as $result) {
                if ($this->config->get($result['code'] . '_status')) {
                    $this->load->model('extension/payment/' . $result['code']);

                    $method = $this->{'model_extension_payment_' . $result['code']}->getMethod($this->session->data['payment_address'], $total);

                    if ($method) {
                        if ($recurring) {
                            if (property_exists($this->{'model_extension_payment_' . $result['code']}, 'recurringPayments') && $this->{'model_extension_payment_' . $result['code']}->recurringPayments()) {
                                $method_data[$result['code']] = $method;
                            }
                        } else {
                            $method_data[$result['code']] = $method;
                        }
                    }
                }
            }

            $sort_order = array();

            foreach ($method_data as $key => $value) {
                $sort_order[$key] = $value['sort_order'];
            }

            array_multisort($sort_order, SORT_ASC, $method_data);


            $this->session->data['payment_methods'] = $method_data;


            $data['payment_methods'] = $method_data;


            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $this->response->setOutput($this->load->view('checkout/onepagecheckout', $data));
        }

    }


    public function validate_form()
    {
        $this->error = [];
        if ((utf8_strlen(trim($this->request->post['firstname'])) < 1) || (utf8_strlen(trim($this->request->post['firstname'])) > 42)) {

            $data['error']['firstname'] = $this->language->get('error_firstname');
        }

        if ((utf8_strlen(trim($this->request->post['telephone'])) < 9) || (utf8_strlen(trim($this->request->post['telephone'])) > 16)) {
            //var_dump($this->request->post['telephone']);exit;
            $data['error']['telephone'] = $this->language->get('error_telephone');

        }

        if ((utf8_strlen(trim($this->request->post['address_1'])) < 1) || (utf8_strlen(trim($this->request->post['address_1'])) > 92)) {
            $data['error']['address_1'] = $this->language->get('error_address_1');
        }
        if ((utf8_strlen(trim($this->request->post['city'])) < 1) || (utf8_strlen(trim($this->request->post['city'])) > 32)) {
            $data['error']['city'] = $this->language->get('error_city');
        }
        if (!empty($data['error'])) {
            $this->errors = $data['error'];
            return false;
        } else
            return true;
    }


}
