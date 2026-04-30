<?php
/**
 * MageMe
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the MageMe End User License Agreement
 * (https://mageme.com/license/) applicable to the Pro tier add-on of the
 * MageMe_EUWithdrawal module. Pro tiers require a paid Production licence.
 * See the LICENSE file in this package for the verbatim EULA text.
 *
 * Copyright (c) MageMe (https://mageme.com)
 **/

use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'MageMe_EUWithdrawalReceiptVerify',
    __DIR__
);
