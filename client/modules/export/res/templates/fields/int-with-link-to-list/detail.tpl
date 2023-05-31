{{#if isNotEmpty}}<a href="javascript:" title="{{value}}" data-action="showList">{{value}}</a>{{else}}
    {{#if valueIsSet}}{{{translate 'None'}}}{{else}}...{{/if}}
{{/if}}
