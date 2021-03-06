/*global define*/
define(function (require) {
    'use strict';

    var EmailTemplateView,
        $ = require('jquery'),
        _ = require('underscore'),
        BaseView= require('oroui/js/app/views/base/view');

    /**
     * @export oroemail/js/app/views/email-template-view
     */
    EmailTemplateView = BaseView.extend({
        events: {
            'change': 'selectionChanged'
        },
        target: null,

        /**
         * Constructor
         *
         * @param options {Object}
         */
        initialize: function (options) {
            this.template = $('#emailtemplate-chooser-template').html();
            this.target = options.target;

            this.listenTo(this.collection, 'reset', this.render);
            if (!$(this.target).val()) {
                this.selectionChanged();
            }
        },

        /**
         * onChange event listener
         */
        selectionChanged: function () {
            var entityId = this.$el.val();
            this.collection.setEntityId(entityId.split('\\').join('_'));
            if (entityId) {
                this.collection.fetch({reset: true});
            } else {
                this.collection.reset();
            }
        },

        render: function () {
            $(this.target).val('').trigger('change');
            $(this.target).find('option[value!=""]').remove();
            if (this.collection.models.length > 0) {
                $(this.target).append(_.template(this.template, {entities: this.collection.models}));
            }
        }
    });

    return EmailTemplateView;
});
