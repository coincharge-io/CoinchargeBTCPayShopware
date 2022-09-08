<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Entity\Order;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class CoinchargeOrderEntity extends Entity
{
    use EntityIdTrait;

    protected $order_id;
    protected $orderNumber;
    protected $invoiceId;
    protected $paymentMethod;
    protected $cryptoCode;
    protected $destination;
    protected $paymentLink;
    protected $rate;
    protected $paymentMethodPaid;
    protected $totalPaid;
    protected $due;
    protected $amount;
    protected $networkFee;
    protected $providedComment;
    protected $consumedLightningAddress;
    
    public function getOrderId(){
        return $this->order_id;
    }
    public function setOrderId($order_id){
         $this->order_id=$order_id;
    }
    public function getInvoiceId(){
        return $this->invoiceId;
    }
    public function setInvoiceId($invoiceId){
         $this->invoiceId=$invoiceId;
    }
    public function getOrderNumber(){
        return $this->orderNumber;
    }
    public function setOrderNumber($orderNumber){
         $this->orderNumber=$orderNumber;
    }
    public function getPaymentMethod(){
        return $this->paymentMethod;
    }
    public function setPaymentMethod($paymentMethod){
         $this->paymentMethod=$paymentMethod;
    }
    public function getCryptoCode(){
        return $this->cryptoCode;
    }
    public function setCryptoCode($cryptoCode){
         $this->cryptoCode=$cryptoCode;
    }
    public function destination(){
        return $this->destination;
    }
    public function setDestination($destination){
         $this->destination=$destination;
    }
    public function getPaymentLink(){
        return $this->paymentLink;
    }
    public function setPaymentLink($paymentLink){
         $this->paymentLink=$paymentLink;
    }
    public function getRate(){
        return $this->rate;
    }
    public function setRate($rate){
         $this->rate=$rate;
    }
    public function getPaymentMethodPaid(){
        return $this->paymentMethodPaid;
    }
    public function setPaymentMethodPaid($paymentMethodPaid){
         $this->paymentMethodPaid=$paymentMethodPaid;
    }
    public function getTotalPaid(){
        return $this->totalPaid;
    }
    public function setTotalPaid($totalPaid){
         $this->totalPaid=$totalPaid;
    }
    public function getDue(){
        return $this->due;
    }
    public function setDue($due){
         $this->due=$due;
    }
    
    public function getAmount(){
        return $this->amount;
    }
    public function setAmount($amount){
         $this->amount=$amount;
    }
    public function getNetworkFee(){
        return $this->networkFee;
    }
    public function setNetworkFee($networkFee){
         $this->networkFee=$networkFee;
    }
    
    public function getProvidedComment(){
        return $this->providedComment;
    }
    public function setProvidedComment($providedComment){
         $this->providedComment=$providedComment;
    }
    public function getConsumedLightningAddress(){
        return $this->consumedLightningAddress;
    }
    public function setConsumedLightningAddress($consumedLightningAddress){
         $this->consumedLightningAddress=$consumedLightningAddress;
    }
}