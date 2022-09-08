<?php declare(strict_types=1);

namespace Coincharge\ShopwareBTCPay\Entity\Payment;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class CoinchargePaymentEntity extends Entity
{
    use EntityIdTrait;
    protected $order_id;
    protected $receivedDate;
    protected $value;
    protected $fee;
    protected $status;
    protected $destination;
    public function getOrderId(){
        return $this->order_id;
    }
    public function setOrderId($order_id){
         $this->order_id=$order_id;
    }
    public function getReceivedDate(){
        return $this->receivedDate;
    }
    public function setReceivedDate($receivedDate){
         $this->receivedDate=$receivedDate;
    }
    public function getValue(){
        return $this->value;
    }
    public function setValue($value){
         $this->value=$value;
    }
    public function getFee(){
        return $this->fee;
    }
    public function setFee($fee){
         $this->fee=$fee;
    }
    public function getStatus(){
        return $this->status;
    }
    public function setStatus($status){
         $this->status=$status;
    }
    public function getDestination(){
        return $this->destination;
    }
    public function setDestination($destination){
         $this->destination=$destination;
    }
    
}