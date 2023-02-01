<?php

namespace Webkul\UI\DataGrid\Traits;

trait ProvideExport
{
    /**
     * Export data.
     *
     * @return object
     */
    public function export()
    {
        if ($this->export) {
            $this->init();

            $this->addColumns();

            $this->prepareTabFilters();

            $this->prepareActions();

            $this->prepareMassActions();

            $this->prepareQueryBuilder();

            $this->getCollection();

            $this->transformColumnsForExport();

            return $this->collection;
        }

        return [];
    }

    /**
     * Finalyzation for export columns.
     *
     * @return void
     */
    public function transformColumnsForExport()
    {
        $this->collection->transform(function ($record) {
            $this->transformColumns($record);

            return $record;
        });
    }
}
