<?php

namespace Webkul\Admin\DataGrids\Contact;

use Illuminate\Support\Facades\DB;
use Webkul\Contact\Repositories\PersonRepository;
use Webkul\UI\DataGrid\DataGrid;

class OrganizationDataGrid extends DataGrid
{
    /**
     * Person repository instance.
     *
     * @var \Webkul\Contact\Repositories\PersonRepository
     */
    protected $personRepository;

    /**
     * Create datagrid instance.
     *
     * @return void
     */
    public function __construct(PersonRepository $personRepository)
    {
        parent::__construct();

        $this->personRepository = $personRepository;
        $this->export = true;#bouncer()->hasPermission('contacts.persons.export') ? true : false;
        $this->itemsPerPage = 30;
    }

    public function transformColumnsForExport()
    {
        $this->collection->transform(function ($record) {

            #$this->transformColumns($record);

            $address = json_decode($record->address);
            foreach ($address as $field => $value) {
                $record->$field = $value;
            }

            #dd($record);

            return $record;
        });
    }

    /**
     * Prepare query builder.
     *
     * @return void
     */
    public function prepareQueryBuilder()
    {


        $queryBuilder = DB::table('organizations')
        ->addSelect(
            DB::raw(
                '
                organizations.id,
                organizations.name,
                organizations.address,
                organizations.created_at,
               (
                    SELECT
                        lead_sources.name
                    FROM
                        attribute_values LEFT JOIN attributes ON attribute_values.attribute_id = attributes.id LEFT JOIN lead_sources on attribute_values.integer_value = lead_sources.id
                    WHERE
                        attributes.code = "source"
                        AND
                        attribute_values.entity_type = "organizations"
                        AND
                        attribute_values.entity_id = organizations.id
                ) as source'
            )
        );

        $this->addFilter('id', 'organizations.id');

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
            'index'    => 'id',
            'label'    => trans('admin::app.datagrid.id'),
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
            'index'    => 'source',
            'label'    => 'Source',
            'type'     => 'string',
            'sortable' => false,
            'searchable' => false
        ]);

        $this->addColumn([
            'index'      => 'persons_count',
            'label'      => trans('admin::app.datagrid.persons_count'),
            'type'       => 'string',
            'searchable' => false,
            'sortable'   => false,
            'filterable' => false,
            'closure'    => function ($row) {
                $personsCount = $this->personRepository->findWhere(['organization_id' => $row->id])->count();

                $route = urldecode(route('admin.contacts.persons.index', ['organization[in]' => $row->id]));

                return "<a href='" . $route . "'>" . $personsCount . "</a>";
            },
        ]);

        $this->addColumn([
            'index'    => 'created_at',
            'label'    => trans('admin::app.datagrid.created_at'),
            'type'     => 'date_range',
            'sortable' => true,
            'closure'  => function ($row) {
                return core()->formatDate($row->created_at);
            },
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
            'route'  => 'admin.contacts.organizations.edit',
            'icon'   => 'pencil-icon',
        ]);

        $this->addAction([
            'title'        => trans('ui::app.datagrid.delete'),
            'method'       => 'DELETE',
            'route'        => 'admin.contacts.organizations.delete',
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
            'action' => route('admin.contacts.organizations.mass_delete'),
            'method' => 'PUT',
        ]);
    }
}
