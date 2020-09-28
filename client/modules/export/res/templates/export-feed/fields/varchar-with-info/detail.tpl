{{#if isNotEmpty}}
    <div>
        {{value}}{{#if isRequired}} *{{/if}}
    </div>
    {{#if extraInfo}}
        <span class="text-muted small">{{{extraInfo}}}</span>
    {{/if}}
{{else}}
    {{#if valueIsSet}}
        {{{translate 'None'}}}
    {{else}}
        ...
    {{/if}}
{{/if}}