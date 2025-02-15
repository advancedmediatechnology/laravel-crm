<?php

namespace Webkul\Admin\DataGrids\Product;

use Webkul\UI\DataGrid\DataGrid;
use Illuminate\Support\Facades\DB;

class ProductDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return void
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('products')
            ->addSelect(DB::raw('
                products.id,
                products.sku,
                products.name,
                products.price,
                products.quantity,
                (SELECT attribute_values.text_value FROM attribute_values LEFT JOIN attributes ON attribute_values.attribute_id = attributes.id WHERE attributes.code = "image" AND attribute_values.entity_type = "products" AND attribute_values.entity_id = products.id) as cover'
            )
        );

        $this->addFilter('id', 'products.id');

        $this->setQueryBuilder($queryBuilder);
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function addColumns()
    {

        $this->addColumn([
            'index'    => 'cover',
            'label'    => 'Cover',
            'type'     => 'image',
            'closure' => function($row){
                return '<a href="'. url('storage/' . $row->cover).'" target=\"_blank\" "><img height="80" src="' . url('storage/' . $row->cover) . '"></a>';
            },
            'searchable' => false
        ]);

        $this->addColumn([
            'index'    => 'sku',
            'label'    => trans('admin::app.datagrid.sku'),
            'type'     => 'string',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'name',
            'label'    => trans('admin::app.datagrid.name'),
            'type'     => 'string',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index'    => 'price',
            'label'    => trans('admin::app.datagrid.price'),
            'type'     => 'string',
            'sortable' => true,
            'closure'  => function ($row) {
                return round($row->price, 2);
            },
        ]);

        $this->addColumn([
            'index'    => 'quantity',
            'label'    => trans('admin::app.datagrid.quantity'),
            'type'     => 'string',
            'sortable' => true,
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        $this->addAction([
            'title'  => trans('ui::app.datagrid.edit'),
            'method' => 'GET',
            'route'  => 'admin.products.edit',
            'icon'   => 'pencil-icon',
        ]);

        $this->addAction([
            'title'        => trans('ui::app.datagrid.delete'),
            'method'       => 'DELETE',
            'route'        => 'admin.products.delete',
            'confirm_text' => trans('ui::app.datagrid.massaction.delete', ['resource' => 'user']),
            'icon'         => 'trash-icon',
        ]);
    }

    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepareMassActions()
    {
        $this->addMassAction([
            'type'   => 'delete',
            'label'  => trans('ui::app.datagrid.delete'),
            'action' => route('admin.products.mass_delete'),
            'method' => 'PUT',
        ]);
    }
}
