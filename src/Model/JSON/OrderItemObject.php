<?php

namespace OperatorHub\Model\JSON;

/**
 * @link https://github.com/Junction6/API/blob/V1/Docs/appendix.md#orderitemobject
 * @property string         $ItemStatus
 * @property int            $OrderID
 * @property float          $UnitPrice
 * @property string         $FeeOrDiscount
 * @property float          $FeeOrDiscountAmount
 * @property int            $Quantity
 * @property string         $RedeemableDate
 * @property SupplierObject $Supplier
 */
class OrderItemObject extends JSONObject
{

}
