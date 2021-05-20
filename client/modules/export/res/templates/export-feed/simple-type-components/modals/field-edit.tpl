<div class="edit-container record">
    <div class="row">
        <div class="cell col-sm-6 form-group">
            <label class="control-label">
                <span class="label-text">{{translate 'field' scope='ExportFeed' category='fields'}}</span>
            </label>
            <div class="field" data-name="field">{{{field}}}</div>
        </div>
    </div>
    <div class="row">
        <div class="cell col-sm-6 form-group">
            <label class="control-label">
                <span class="label-text">{{translate 'columnType' scope='ExportFeed' category='fields'}}</span>
            </label>
            <div class="field" data-name="columnType">{{{columnType}}}</div>
            <div class="field" data-name="attributeColumn">{{{attributeColumn}}}</div>
        </div>
        <div class="cell col-sm-6 form-group">
            <label class="control-label">
                <span class="label-text">{{translate 'column' scope='ExportFeed' category='fields'}}</span>
            </label>
            <div class="field" data-name="column">{{{column}}}</div>
        </div>
    </div>
    <div class="row">
        <div class="cell col-sm-6 form-group">
            <label class="control-label">
                <span class="label-text">{{translate 'exportBy' scope='ExportFeed' category='fields'}}</span>
            </label>
            <div class="field" data-name="exportBy">{{{exportBy}}}</div>
        </div>
        <div class="cell col-sm-6 form-group">
            <label class="control-label">
                <span class="label-text">{{translate 'exportIntoSeparateColumns' scope='ExportFeed' category='fields'}}</span>
            </label>
            <div class="field" data-name="exportIntoSeparateColumns">{{{exportIntoSeparateColumns}}}</div>
        </div>
    </div>
</div>