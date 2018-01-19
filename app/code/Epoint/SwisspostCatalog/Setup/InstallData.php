<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Epoint\SwisspostCatalog\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Catalog\Setup\CategorySetupFactory;

/**
 * Class InstallData
 */
class InstallData implements InstallDataInterface
{
    /**
     * Group name for the group which will contain all the odoo attributes that don't exist in the system by default
     *
     * @const ODOO_GROUP_NAME
     */
    const ODOO_GROUP_NAME = 'Odoo Connector';

    /**
     * EAV setup factory
     *
     * @var EavSetupFactory
     */
    private $eavSetupFactory;

    /**
     * Category setup factory
     *
     * @var $categorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * Init
     *
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * Install new Swatch entity
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @return void
     */
    public function install(ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        /* get entity type id so that attribute are only assigned to catalog_product */
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);
        /* Here we have fetched all attribute set as we want attribute group to show under all attribute set.*/
        $attributeSetIds = $eavSetup->getAllAttributeSetIds($entityTypeId);

        foreach ($attributeSetIds as $attributeSetId) {
            $eavSetup->addAttributeGroup(
                $entityTypeId, $attributeSetId, self::ODOO_GROUP_NAME, 19
            );
        }

        // Define accepted product types
        $productTypes = [
            \Magento\Catalog\Model\Product\Type::TYPE_SIMPLE,
            \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL,
            \Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE,
            \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE,
        ];

        $productTypes = join(',', $productTypes);

        /**
         * Install eav entity types to the eav/entity_type table
         */
        $eavSetup->addAttribute(
            Product::ENTITY,
            'ean13',
            [
                'group'                      => self::ODOO_GROUP_NAME,
                'input'                      => 'text',
                'type'                       => 'text',
                'label'                      => 'Ean13',
                'backend'                    => '',
                'visible'                    => true,
                'required'                   => false,
                'user_defined'               => true,
                'searchable'                 => false,
                'filterable'                 => false,
                'comparable'                 => false,
                'visible_on_front'           => false,
                'visible_in_advanced_search' => false,
                'is_html_allowed_on_front'   => false,
                'apply_to'                   => $productTypes,
                'global'                     => ScopedAttributeInterface::SCOPE_GLOBAL,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'width',
            [
                'group'                      => self::ODOO_GROUP_NAME,
                'input'                      => 'text',
                'type'                       => 'text',
                'label'                      => 'Width',
                'backend'                    => '',
                'visible'                    => true,
                'required'                   => false,
                'user_defined'               => true,
                'searchable'                 => false,
                'filterable'                 => false,
                'comparable'                 => false,
                'visible_on_front'           => false,
                'visible_in_advanced_search' => false,
                'is_html_allowed_on_front'   => false,
                'apply_to'                   => $productTypes,
                'global'                     => ScopedAttributeInterface::SCOPE_GLOBAL,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'height',
            [
                'group'                      => self::ODOO_GROUP_NAME,
                'input'                      => 'text',
                'type'                       => 'text',
                'label'                      => 'Height',
                'backend'                    => '',
                'visible'                    => true,
                'required'                   => false,
                'user_defined'               => true,
                'searchable'                 => false,
                'filterable'                 => false,
                'comparable'                 => false,
                'visible_on_front'           => false,
                'visible_in_advanced_search' => false,
                'is_html_allowed_on_front'   => false,
                'apply_to'                   => $productTypes,
                'global'                     => ScopedAttributeInterface::SCOPE_GLOBAL,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'diameter',
            [
                'group'                      => self::ODOO_GROUP_NAME,
                'input'                      => 'text',
                'type'                       => 'text',
                'label'                      => 'Diameter',
                'backend'                    => '',
                'visible'                    => true,
                'required'                   => false,
                'user_defined'               => true,
                'searchable'                 => false,
                'filterable'                 => false,
                'comparable'                 => false,
                'visible_on_front'           => false,
                'visible_in_advanced_search' => false,
                'is_html_allowed_on_front'   => false,
                'apply_to'                   => $productTypes,
                'global'                     => ScopedAttributeInterface::SCOPE_GLOBAL,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'length',
            [
                'group'                      => self::ODOO_GROUP_NAME,
                'input'                      => 'text',
                'type'                       => 'text',
                'label'                      => 'Length',
                'backend'                    => '',
                'visible'                    => true,
                'required'                   => false,
                'user_defined'               => true,
                'searchable'                 => false,
                'filterable'                 => false,
                'comparable'                 => false,
                'visible_on_front'           => false,
                'visible_in_advanced_search' => false,
                'is_html_allowed_on_front'   => false,
                'apply_to'                   => $productTypes,
                'global'                     => ScopedAttributeInterface::SCOPE_GLOBAL,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'weight_net',
            [
                'group'                      => self::ODOO_GROUP_NAME,
                'input'                      => 'text',
                'type'                       => 'text',
                'label'                      => 'Weight Net',
                'backend'                    => '',
                'visible'                    => true,
                'required'                   => false,
                'user_defined'               => true,
                'searchable'                 => false,
                'filterable'                 => false,
                'comparable'                 => false,
                'visible_on_front'           => false,
                'visible_in_advanced_search' => false,
                'is_html_allowed_on_front'   => false,
                'apply_to'                   => $productTypes,
                'global'                     => ScopedAttributeInterface::SCOPE_GLOBAL,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'uom_name',
            [
                'group'                      => self::ODOO_GROUP_NAME,
                'input'                      => 'text',
                'type'                       => 'text',
                'label'                      => 'UOM Name',
                'backend'                    => '',
                'visible'                    => true,
                'required'                   => false,
                'user_defined'               => true,
                'searchable'                 => false,
                'filterable'                 => false,
                'comparable'                 => false,
                'visible_on_front'           => false,
                'visible_in_advanced_search' => false,
                'is_html_allowed_on_front'   => false,
                'apply_to'                   => $productTypes,
                'global'                     => ScopedAttributeInterface::SCOPE_GLOBAL,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'volume',
            [
                'group'                      => self::ODOO_GROUP_NAME,
                'input'                      => 'text',
                'type'                       => 'decimal',
                'label'                      => 'Volume',
                'backend'                    => '',
                'visible'                    => true,
                'required'                   => false,
                'user_defined'               => true,
                'searchable'                 => false,
                'filterable'                 => false,
                'comparable'                 => false,
                'visible_on_front'           => false,
                'visible_in_advanced_search' => false,
                'is_html_allowed_on_front'   => false,
                'apply_to'                   => $productTypes,
                'global'                     => ScopedAttributeInterface::SCOPE_GLOBAL,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'manufacturer_website',
            [
                'group'                      => self::ODOO_GROUP_NAME,
                'input'                      => 'text',
                'type'                       => 'text',
                'label'                      => 'Manufacturer Website',
                'backend'                    => '',
                'visible'                    => true,
                'required'                   => false,
                'user_defined'               => true,
                'searchable'                 => false,
                'filterable'                 => false,
                'comparable'                 => false,
                'visible_on_front'           => false,
                'visible_in_advanced_search' => false,
                'is_html_allowed_on_front'   => false,
                'apply_to'                   => $productTypes,
                'global'                     => ScopedAttributeInterface::SCOPE_GLOBAL,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'sale_delay',
            [
                'group'                      => self::ODOO_GROUP_NAME,
                'input'                      => 'text',
                'type'                       => 'int',
                'label'                      => 'Sale Delay',
                'backend'                    => '',
                'visible'                    => true,
                'required'                   => false,
                'user_defined'               => true,
                'searchable'                 => false,
                'filterable'                 => false,
                'comparable'                 => false,
                'visible_on_front'           => false,
                'visible_in_advanced_search' => false,
                'is_html_allowed_on_front'   => false,
                'apply_to'                   => $productTypes,
                'global'                     => ScopedAttributeInterface::SCOPE_GLOBAL,
            ]
        );

        $eavSetup->addAttribute(
            Product::ENTITY,
            'odoo_id',
            [
                'group'    => self::ODOO_GROUP_NAME,
                'visible'  => true,
                'required' => false,
                'label'    => 'Odoo Id',
                'apply_to' => $productTypes,
            ]
        );
    }
}
