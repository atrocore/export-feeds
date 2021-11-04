<div class="row">
    <div class="cell form-group col-sm-6 col-xs-12" data-name="entity">
        <label class="control-label" data-name="entity"><span class="label-text">{{translate 'entity' scope=scope category='fields'}}</span></label>
        <div class="field" data-name="entity">
            {{{entity}}}
        </div>
    </div>
    <div class="cell form-group col-sm-6 col-xs-12" data-name="delimiter">
        <label class="control-label" data-name="delimiter"><span class="label-text">{{translate 'delimiter' scope=scope category='fields'}}</span></label>
        <div class="field" data-name="delimiter">
            {{{delimiter}}}
        </div>
    </div>
</div>
<div class="row">
    <div class="cell form-group col-sm-6 col-xs-12" data-name="emptyValye">
        <label class="control-label" data-name="emptyValue"><span class="label-text">{{translate 'emptyValue' scope=scope category='fields'}}</span></label>
        <div class="field" data-name="emptyValue">
            {{{emptyValue}}}
        </div>
    </div>
    <div class="cell form-group col-sm-6 col-xs-12" data-name="nullValue">
        <label class="control-label" data-name="nullValue"><span class="label-text">{{translate 'nullValue' scope=scope category='fields'}}</span></label>
        <div class="field" data-name="nullValue">
            {{{nullValue}}}
        </div>
    </div>
</div>
<div class="row">
    <div class="cell form-group col-sm-6 col-xs-12" data-name="thousandSeparator">
        <label class="control-label" data-name="thousandSeparator"><span class="label-text">{{translate 'thousandSeparator' scope=scope category='fields'}}</span></label>
        <div class="field" data-name="thousandSeparator">
            {{{thousandSeparator}}}
        </div>
    </div>
    <div class="cell form-group col-sm-6 col-xs-12" data-name="decimalMark">
        <label class="control-label" data-name="decimalMark"><span class="label-text">{{translate 'decimalMark' scope=scope category='fields'}}</span></label>
        <div class="field" data-name="decimalMark">
            {{{decimalMark}}}
        </div>
    </div>
</div>
<div class="row">
    <div class="cell form-group col-sm-6 col-xs-12" data-name="allFields">
        <label class="control-label" data-name="allFields"><span class="label-text">{{translate 'allFields' scope=scope category='fields'}}</span></label>
        <div class="field" data-name="allFields">
            {{{allFields}}}
        </div>
    </div>
    <div class="cell form-group col-sm-6 col-xs-12" data-name="markForNotLinkedAttribute">
        <label class="control-label" data-name="markForNotLinkedAttribute"><span class="label-text">{{translate 'markForNotLinkedAttribute' scope=scope category='fields'}}</span></label>
        <div class="field" data-name="markForNotLinkedAttribute">
            {{{markForNotLinkedAttribute}}}
        </div>
    </div>
</div>
<div class="panel panel-default panel-configurator">
    <div class="panel-heading">
        <div class="pull-right btn-group">
            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-name="configuratorActions" data-toggle="dropdown">
                <span class="fas fa-plus"></span>
            </button>
            <ul class="dropdown-menu">
                {{#each configuratorActions}}
                <li><a href="javascript:" class="action" data-action="{{action}}">{{label}}</a></li>
                {{/each}}
            </ul>
        </div>
        <h4 class="panel-title">{{translate 'Configurator' scope=scope category='labels'}}</h4>
    </div>
    <div class="mapping-container">
        <div class="list-container">{{{configurator}}}</div>
    </div>
</div>
<style>
    .mapping-container .list table td {
        overflow: hidden;
    }
    .mapping-container .list table td[data-name="default"] {
        overflow: visible;
    }
    .mapping-container .no-data {
        padding: 14px;
    }
</style>