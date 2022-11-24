<?php
include(dirname(__FILE__) . '/../config/config.inc.php');
require_once _PS_ROOT_DIR_.'/vendor/autoload.php';

function getAllProducts($category)
{
    $id_lang = (int) Context::getContext()->language->id;
    $start = 0;
    $limit = 100000;
    $order_by = 'id_product';
    $order_way = 'DESC';
    $id_category = null;
    $only_active = true;
    $context = null;
    $all_products = Product::getProducts($id_lang, $start, $limit, $order_by, $order_way, $id_category, $only_active, $context);
    $plist=[];

    foreach($all_products as $product){
        $product_obj = new Product($product['id_product']);
        foreach ($product_obj->getCategories() as $cat){
            if ($cat==$category){
                $plist[]=$product_obj;
            }
        }
    }
    return $plist;
}

function changePriceAll($price,$category,$warehouse,$opt){
    $products = getAllProducts($category);
    $price=str_replace(' ','',$price);
    foreach ($products as $product) {
        if(!$warehouse){
            $new_price = getPrice($product->price, $price, $opt);
        }
        changePrice($product,$new_price??$price,$warehouse, $opt);
    }
}

function changePrice($product, $price, $warehouse, $opt, $product_attribute=0)
{
    if(!$warehouse) {
        echo 'Actualizando producto '.$product->id.' precio anterior '.$product->price.', precio nuevo: '.$price."\n";
        $product->price = $price;
        $product->update();
    }else{
        $sprice=new SpecificPrice();
        $sprice->id_product=$product->id;
        $sprice->id_product_attribute=$product_attribute;
        $sprice->reduction=$opt=='porc'?$price/100:$price;
        if($opt=='fijo'){
            $sprice->reduction=0;
        }
        $sprice->reduction_tax=0;
        $sprice->reduction_type=$opt=='porc'?'percentage':'amount';
        $sprice->from=date('Y-m-d H:i:s');
        $sprice->to='0000-00-00 00:00:00';
        $sprice->id_shop=1;
        $sprice->id_shop_group=0;
        $sprice->id_country=0;
        $sprice->id_currency=0;
        $sprice->id_group=$warehouse;
        $sprice->id_customer=0;
        $sprice->from_quantity=1;
        $sprice->id_specific_price_rule=0;
        $sprice->price=$opt=='fijo'?$price:-1.0;
        $sprice->id_cart=0;
        try{
            $sprice->add();
            echo 'Agregando descuento para el grupo '.$warehouse.' en el producto '.$product->id. ' product attribute '. $product_attribute.($sprice->reduction!=0 ?' con descuento de '.$sprice->reduction:' con precio '.$sprice->price)."\n";
        }catch(Exception $e){
            echo'Excepcion insertando descuento en el producto '.$product->id.' product attribute '. $product_attribute.': '.$e->getMessage()."\n";
        }
    }
}

function changeCombinations($attribute_id, $price, $is_warehouse, $opt, $id_category)
{
    $db = Db::getInstance();
    $request = 'SELECT * FROM `ps_attribute` WHERE `id_attribute` = '.$attribute_id;
    $result = $db->executeS($request);

    if (empty($result))
        return 0;

    $request = 'SELECT id_product, id_product_attribute FROM ps_product_attribute WHERE id_product_attribute IN (SELECT id_product_attribute FROM ps_product_attribute_combination 
WHERE id_attribute ='. $attribute_id .')';
    if($id_category){
        $request .= ' AND id_product in (SELECT id_product FROM ps_category_product WHERE id_category = '.$id_category. ')';
    }
    $product_combinations = $db->executeS($request);
    foreach ($product_combinations as $product_combination){
        $product = new Product($product_combination['id_product']);
        $combination = new Combination($product_combination['id_product_attribute']);
        if ($is_warehouse){
            changePrice($product,$price,$is_warehouse,$opt,$combination->id);
        }else{
            $old_price = $product->price + $combination->price;
            $new_price = getPrice($old_price, $price, $opt);
            $combination->price = $new_price - $old_price;
           try{
               $combination->update();
               echo 'Combinacion actualizada, producto '.$product->id .' atributo '. $combination->id. ' precio '.$combination->price;
           }catch( Exception $e){
               echo 'error actualizando combinacion producto '.$product->id .' atributo '. $combination->id. ' precio '.$combination->price . 'error : '. $e->getMessage();
           }

        }
    }

}

function getPrice($old_price, $price, $opt){
    $new_price = $price;
    if ($opt == 'porc') {
        $new_price = $old_price + $old_price * (float)($price / 100);
    } elseif ($opt == 'sum') {
        $new_price = $old_price + (float)$price;
    }
    return $new_price;
}

if (!isset($_GET["ws_key"]) || $_GET["ws_key"] != "L5G8YNYZ9SUR5PIAPIBE25YZKMZ3V4HW")
    die('unauthorized');
$id_category =Tools::getValue('categoria');
$price = Tools::getValue('precio');
$opt = Tools::getValue('op');
$is_warehouse = Tools::getValue('mayorista');
$attribute_id = Tools::getValue('id_atributo');
if($attribute_id){
    changeCombinations($attribute_id, $price,$is_warehouse,$opt, $id_category);
}elseif($id_category && $price){
    changePriceAll($price,$id_category,$is_warehouse,$opt);
}
