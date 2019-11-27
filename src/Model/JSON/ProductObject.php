<?php

namespace OperatorHub\Model\JSON;

/**
 * @property string                                                               $Title
 * @property bool                                                                 $Recommended
 * @property bool                                                                 $HotDeal
 * @property bool                                                                 $Affiliate
 * @property string                                                               $AffiliatelURL
 * @property bool                                                                 $AllowAgencyPurchase
 * @property bool                                                                 $AllowCustomerPurchase
 * @property bool                                                                 $ShowOnWhitelabel
 * @property string                                                               $AgencyCommissionType
 * @property float                                                                $AgencyCommission
 * @property float                                                                $Price
 * @property float                                                                $Weight
 * @property string                                                               $Model
 * @property bool                                                                 $FeaturedProduct
 * @property bool                                                                 $AllowPurchase
 * @property string                                                               $InternalItemID
 * @property string                                                               $Features
 * @property string                                                               $WhatToBring
 * @property string                                                               $Itinerary
 * @property string                                                               $WhitelabelContentFooter
 * @property string                                                               $Content
 * @property string                                                               $BookingInfo
 * @property SupplierObject[]                                                     $Supplier
 * @property VariationObject[]                                                    $Variations
 * @property string[]                                                             $NestedTags
 * @property LocationObject[]                                                     $Location
 * @property ProductPickupLocationObject[]|ProductTimetablePickupLocationObject[] $ProductPickupLocations
 * @property SpecialPriceObject[]                                                 $SpecialPrices
 * @property ExtraProductObject[]                                                 $ExtraProducts
 */
class ProductObject extends JSONObject
{

}
