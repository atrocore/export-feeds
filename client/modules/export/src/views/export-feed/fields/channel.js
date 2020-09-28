

Espo.define('export:views/export-feed/fields/channel', 'views/fields/link',
    Dep => Dep.extend({

        foreignScope: 'Channel'

    })
);