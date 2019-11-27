<?php

namespace OperatorHub\Model\JSON;

/**
 * @property string         $VoucherTitle
 * @property string         $GiftVoucherCode
 * @property CustomerObject $Customer           The customer who purchased the gift voucher
 * @property CustomerObject $AssignedToCustomer The customer assigned the gift card, if any
 * @property string         $ExpiryDate
 * @property float          $AmountRemaining
 * @property string         $Status
 */
class GiftVoucherOrderItemObject extends ProductOrderItemObject
{

}
