<?php

/**
 * Product Category provides a way to hierartically categorise products.
 *
 * It contains functions for versioning child products
 *
 * @package shop
 */
class ProductCategory extends Page implements i18nEntityProvider
{
    private static $belongs_many_many    = array(
        'Products' => 'Product',
    );

    private static $singular_name        = "Category";

    private static $plural_name          = "Categories";

    private static $icon                 = 'cms/images/treeicons/folder';

    private static $default_child        = 'Product';

    private static $include_child_groups = true;

    private static $page_length          = 12;

    private static $must_have_price      = true;

    private static $sort_options         = array(
        'Alphabetical' => 'URLSegment',
        'Price'        => 'BasePrice',
    );

    /**
     * Retrieve a set of products, based on the given parameters. Checks get query for sorting and pagination.
     *
     * @param string $extraFilter Additional SQL filters to apply to the Product retrieval
     * @param bool   $recursive   include sub-categories
     *
     * @return PaginatedList
     */
    public function ProductsShowable($recursive = true)
    {
        // Figure out the categories to check
        $groupids = array($this->ID);
        if (!empty($recursive) && self::config()->include_child_groups) {
            $groupids += $this->AllChildCategoryIDs();
        }
        $products = Product::get()
            ->leftJoin('Product_ProductCategories', '"Product_ProductCategories"."ProductID" = "Product"."ID"')
            ->filterAny(
                array(
                    'ParentID'                                    => $groupids,
                    'Product_ProductCategories.ProductCategoryID' => $groupids,
                )
            );
        if (self::config()->must_have_price) {
            if (Product::has_extension('ProductVariationsExtension')) {
                $products = $products->filterAny(array(
                    "BasePrice:GreaterThan" => 0,
                    "Variations.Price:GreaterThan" => 0
                ));
            } else {
                $products = $products->filter("BasePrice:GreaterThan", 0);
            }

        }

        $this->extend('updateProductsShowable', $products);

        return $products;
    }

    /**
     * Loop down each level of children to get all ids.
     */
    public function AllChildCategoryIDs()
    {
        $ids = array($this->ID);
        $allids = array();
        do {
            $ids = ProductCategory::get()
                ->filter('ParentID', $ids)
                ->getIDList();
            $allids += $ids;
        } while (!empty($ids));

        return $allids;
    }

    /**
     * Return children ProductCategory pages of this category.
     *
     * @param bool $recursive
     *
     * @return DataList
     */
    public function ChildCategories($recursive = false)
    {
        $ids = array($this->ID);
        if ($recursive) {
            $ids += $this->AllChildCategoryIDs();
        }

        return ProductCategory::get()->filter("ParentID", $ids);
    }

    /**
     * Recursively generate a product menu, starting from the topmost category.
     *
     * @return DataList
     */
    public function GroupsMenu()
    {
        if ($this->Parent() instanceof ProductCategory) {

            return $this->Parent()->GroupsMenu();
        }
        return ProductCategory::get()
            ->filter("ParentID", $this->ID);
    }

    /**
     * Override the nested title defaults, to show deeper nesting in the CMS.
     *
     * @param integer $level     nesting level
     * @param string  $separator seperate nesting with this string
     */
    public function NestedTitle($level = 10, $separator = " > ", $field = "MenuTitle")
    {
        $item = $this;
        while ($item && $level > 0) {
            $parts[] = $item->{$field};
            $item = $item->Parent;
            $level--;
        }
        return implode($separator, array_reverse($parts));
    }

    public function provideI18nEntities()
    {
        $entities = parent::provideI18nEntities();

        // add the sort option keys
        foreach ($this->config()->sort_options as $key => $value) {
            $entities["ProductCategory.$key"] = array(
                $key,
                "Sort by the '$value' field",
            );
        }

        return $entities;
    }
}

class ProductCategory_Controller extends Page_Controller
{
    /**
     * Return the products for this group.
     */
    public function Products($recursive = true)
    {
        $products = $this->ProductsShowable($recursive);
        //sort the products
        $products = $this->getSorter()->sortList($products);

        //paginate the products, if necessary
        $pagelength = ProductCategory::config()->page_length;
        if ($pagelength > 0) {
            $products = PaginatedList::create($products, $this->request);
            $products->setPageLength($pagelength);
            $products->TotalCount = $products->getTotalItems();
        }

        return $products;
    }

    /**
     * Return products that are featured, that is products that have "FeaturedProduct = 1"
     */
    public function FeaturedProducts($recursive = true)
    {
        return $this->ProductsShowable($recursive)
            ->filter("Featured", true);
    }

    /**
     * Return products that are not featured, that is products that have "FeaturedProduct = 0"
     */
    public function NonFeaturedProducts($recursive = true)
    {
        return $this->ProductsShowable($recursive)
            ->filter("Featured", false);
    }

    /**
     * Sorting controls
     *
     * @return ListSorter sorter
     */
    public function getSorter()
    {
        $options = array();
        foreach (ProductCategory::config()->sort_options as $k => $v) {
            // make the label translatable
            $k = _t("ProductCategory.$k", $k);
            $options[$k] = $v;
        }

        $sorter = ListSorter::create($this->request, $options);
        $this->extend('updateSorter', $sorter);

        return $sorter;
    }
}
