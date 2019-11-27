<?php

namespace OperatorHub\Model\JSON;

/**
 * @link https://github.com/Junction6/API/blob/V1/Docs/appendix.md#orderobject
 *
 * @property int                       $ExternalOrderID
 * @property OrderItemObject[]         $Items
 * @property CustomerObject            $Customer
 * @property string                    $Status
 * @property AgencyObject|BranchObject $Agency
 */
class OrderObject extends JSONObject
{

}
