
@push('css')
    <style>
        .content-container {
            overflow: hidden;
        }

        .drag-container {
            overflow: inherit;
        }

        .table {
            height: 100%;
            width: 100%;
        }

        .viewport-height {
            height: calc(100vh - 240px);
        }
        .lead-tag{
            display: inline-block; font-size: 13px; color: #fff; font-weight: bold; padding: 2px 5px; line-height: 15px; border-radius: 3px;
        }
    </style>
@endpush



<div class="content full-page">
    <div class="table">
        <div class="table-header">
            <h1>
                {!! view_render_event('admin.leads.index.header.before') !!}

                {{ Breadcrumbs::render('leads') }}

                {{ __('admin::app.leads.title') }}

                {!! view_render_event('admin.leads.index.header.after') !!}
            </h1>

            @if (bouncer()->hasPermission('leads.create'))
                <div class="table-action">
                    <a href="{{ route('admin.leads.create') }}" class="btn btn-md btn-primary">{{ __('admin::app.leads.create-title') }}</a>
                </div>
            @endif
        </div>

        <div class="table-body viewport-height">
            <kanban-filters></kanban-filters>

            <kanban-component></kanban-component>
        </div>
    </div>
</div>

@push('scripts')
    <script type="text/x-template" id="kanban-filters-tempalte">
        <div class="form-group datagrid-filters">
            <div class="search-filter">
                <i class="icon search-icon input-search-icon"></i>

                <input
                    type="search"
                    class="control"
                    id="search-field"
                    :placeholder="__('ui.datagrid.search')"
                />
            </div>

            <sidebar-filter-custom :columns="columns"></sidebar-filter-custom>

            <div class="filter-right">

                @include('admin::leads.index.view-swither')

                <div class="filter-btn">
                    <div class="grid-dropdown-header" @click="toggleSidebarFilter">
                        <span class="name">{{ __('ui::app.datagrid.filter.title') }}</span>

                        <i class="icon add-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </script>

    <script type="text/x-template" id="kanban-component-tempalte">
        <kanban-board :stages="stage_names" :blocks="leads" @update-block="updateLeadStage">
            <div v-for="(stage, index) in stage_names" :slot="stage" :key="`stage-${stage}`">
                <h2>
                    @{{ stage }}
                    <span class="float-right">@{{ totalCounts[stage] }}</span>
                </h2>

                @if (bouncer()->hasPermission('leads.create'))
                    <a :href="'{{ route('admin.leads.create') }}' + '?stage_id=' + stages[index].id">
                        {{ __('admin::app.leads.create-title') }}
                    </a>
                @endif
            </div>

            <div
                v-for="lead in leads"
                :slot="lead.id"
                :key="`block-${lead.id}`"
                class="lead-block"
                :class="{ 'rotten': lead.rotten_days > 0 ? true : false }"
            >

                <div class="lead-title">@{{ lead.title }}</div>

                <div class="icons">
                    <a :href="'{{ route('admin.leads.view') }}/' + lead.id" class="icon eye-icon"></a>
                    <i class="icon drag-icon"></i>
                </div>

                <div class="lead-person" v-if="lead.lead_handler_name">
                    <i class="icon person-icon"></i>
                    @{{ lead.lead_handler_name }}
                </div>

                <div class="lead-person">
                    <i class="icon avatar-dark-icon"></i>
                    <a :href="`${personIndexUrl}?id[eq]=${lead.person_id}`">
                        @{{ lead.person_name }}
                    </a>
                </div>

                <div class="lead-cost">
                    <i class="icon dollar-circle-icon"></i>@{{ lead.lead_value }}
                </div>

                <span class="lead-tag" v-for="tag in lead.tags" :style={backgroundColor:tag.color} >@{{tag.name}}</span>

            </div>
        </kanban-board>
    </script>

{{--

    {key: 'created_at', value: '2023-01-16,2023-01-31', cond: 'bw'}

    {key: null, value: '10', cond: 'in'}

    --}}

    <script>
        Vue.component('kanban-filters', {
            template: '#kanban-filters-tempalte',

            data: function () {
                return {
                    debounce: [],
                    columns: {
                        'created_at': {
                            'type' : 'date_range',
                            'label' : "{{ trans('admin::app.datagrid.created_at') }}",
                            'values' : [null, null],
                            'filterable' : true
                        },
                        'id': {
                            'type' : 'string',
                            'label' : "ID",
                            'filterable' : true,
                            'index':'id'
                        },
                        'lead_source_id':{
                            'index' : 'lead_source_id',
                            'type':'dropdown',
                            'label' : "Source",
                            'dropdown_options' : @json($data['sources']),
                            'filterable' : true,
                        },
                        'person_id':{
                            'index' : 'person_id',
                            'type':'dropdown',
                            'label' : "Sales person",
                            'dropdown_options' : @json($data['sales_persons']),
                            'filterable' : true,
                        },
                         'tags':{
                            'index' : 'tags',
                            'type':'dropdown',
                            'label' : "Tags",
                            'dropdown_options' : @json($data['tags']),
                            'filterable' : true,
                        },
                        'lead_value':{
                            'type':'value_range',
                            'label':'Value range',
                            'filterable' : true,
                            'index':'lead_value',
                            'values':[null,null]
                        }

                    }
                }
            },

            mounted: function () {
                EventBus.$on('updateFilter', data => {
                    EventBus.$emit('updateKanbanFilter', data);
                });
            },

            methods: {
                toggleSidebarFilter: function () {
                    $('.sidebar-filter').toggleClass('show');
                },
            }
        });

        Vue.component('kanban-component', {

            template: '#kanban-component-tempalte',

            data: function () {
                return {
                    stages: @json($pipeline->stages->toArray()),

                    stage_pagination: {},


                    leads: [],

                    debounce: null,

                    totalCounts: {},

                    personIndexUrl: "{{ route('admin.contacts.persons.index') }}",

                    currentFilters: []
                }
            },

            computed: {
                stage_names: function() {
                    return this.stages.map(stage => stage.name)
                },

                blocks: function() {
                    return this.leads;
                },

            },

            created: function () {
                this.getLeads();

                queueMicrotask(() => {
                    $('#search-field').on('search keyup', ({target}) => {
                        clearTimeout(this.debounce);

                        this.debounce = setTimeout(() => {
                            this.search(target.value)
                        }, 2000);
                    });
                });
            },

            mounted: function () {
                EventBus.$on('updateKanbanFilter', this.updateFilter);



                var self = this;

                $('.drag-inner-list').on('scroll', function() {
                    if ($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight) {
                        var stage = self.getStageByName($(this).attr('data-status'));

                        var pagination = self.stage_pagination[stage.id];

                        if (! pagination.next) {
                            return;
                        }

                        self.getLeads(false, '?page=' + pagination.next + '&pipeline_stage_id=' + stage.id);
                    }
                });
            },

            methods: {
                getLeads: function (searchedKeyword, filterValues) {

                    this.$root.pageLoaded = false;

                    this.$http.get("{{ route('admin.leads.get', request('pipeline_id')) }}" + `${searchedKeyword ? `?search=${searchedKeyword}` : ''}${filterValues || ''}`)
                        .then(response => {

                            this.leads = new Array();

                            this.$root.pageLoaded = true;

                            this.$root.pageLoaded = true;

                            var totalCounts = {};

                            var self = this;

                            this.stages.forEach(function(stage) {
                                if (response.data[stage.id] !== undefined) {
                                    totalCounts[stage.name] = response.data[stage.id]['total'];

                                    self.leads = self.leads.concat(response.data[stage.id]['leads'])

                                    self.stage_pagination[stage.id] = response.data[stage.id]['pagination'];
                                } else {
                                    totalCounts[stage.name] = self.totalCounts[stage.name];
                                }
                            })

                            this.totalCounts = totalCounts;

                            setTimeout(() => {
                                this.toggleEmptyStateIcon();
                            })
                        })
                        .catch(error => {
                            this.$root.pageLoaded = true;
                        });
                },

                getStageByName: function (stageName) {
                    var stages = this.stages.filter(stage => stageName === stage.name)

                    return stages[0];
                },

                updateLeadStage: function (id, stageName) {
                    var stage = this.stages.filter(stage => stage.name === stageName);

                    this.$http.put("{{ route('admin.leads.update') }}/" + id, {'lead_pipeline_stage_id': stage[0].id})
                        .then(response => {
                            window.flashMessages = [{'type': 'success', 'message': response.data.message}];

                            this.$root.addFlashMessages();
                        })
                        .catch(error => {
                            window.flashMessages = [{'type': 'error', 'message': error.response.data.message}];

                            this.$root.addFlashMessages();
                        });
                },

                search: function (searchedKeyword) {
                    this.getLeads(searchedKeyword);
                },

                updateFilter: function (data) {

                    this.currentFilters[data.key] = {
                        cond:data.cond,
                        value:data.value
                    };



                    let filterArray = [];

                    for(let key in this.currentFilters){
                        let filter = this.currentFilters[key];

                        filterArray.push(key+'['+filter.cond+']='+filter.value)
                    }

                    let filterString = (filterArray.length > 0) ? '?'+filterArray.join('&') : '';

                    //let href = data.key ? `?${data.key}[${data.cond}]=${data.value}` : false;
                    let href = data.key ? filterString : false;

                    this.getLeads(false, href);
                },

                toggleEmptyStateIcon: function () {
                    $('.empty-icon-container').remove();

                    $('ul.drag-inner-list').each((index, item) => {
                        if (! $(item).children('.drag-item').length) {
                            $(item).append(`
                                <div class='empty-icon-container disable-drag'>
                                    <div class="icon-text-container">
                                        <i class='icon empty-kanban-icon'></i>
                                        <span>{{ __('admin::app.leads.no-lead') }}</span>
                                    </div>
                                </div>
                            `)
                        }
                    });
                }
            }
        });
    </script>
@endpush
