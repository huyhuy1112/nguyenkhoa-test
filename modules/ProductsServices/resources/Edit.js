Vtiger_Edit_Js("ProductsServices_Edit_Js", {}, {

    getItemType : function() {
        var val = jQuery('[name="item_type"]').val() || '';
        return val.toString().trim().toLowerCase();
    },

    getProductBlock: function () {
        var $block = jQuery('.fieldBlockContainer[data-block="LBL_PRODUCT_INFORMATION"]');
        if ($block.length) return $block;
        return jQuery('.fieldBlockContainer').filter(function () {
            return (jQuery(this).find('.fieldBlockHeader').text() || '').toLowerCase().indexOf('product information') !== -1;
        });
    },

    getServiceBlock: function () {
        var $block = jQuery('.fieldBlockContainer[data-block="LBL_SERVICE_INFORMATION"]');
        if ($block.length) return $block;
        return jQuery('.fieldBlockContainer').filter(function () {
            return (jQuery(this).find('.fieldBlockHeader').text() || '').toLowerCase().indexOf('service information') !== -1;
        });
    },

    toggleBlocks : function() {
        var type = this.getItemType();
        var productBlock = this.getProductBlock();
        var serviceBlock = this.getServiceBlock();

        if (type === 'product') {
            productBlock.show();
            serviceBlock.hide();
        } else if (type === 'service') {
            productBlock.hide();
            serviceBlock.show();
        } else {
            // default: show both if type is empty/other
            productBlock.show();
            serviceBlock.show();
        }
    },

    registerItemTypeEvent : function(){
        var self = this;

        jQuery(document).on('change', '[name="item_type"]', function(){
            self.toggleBlocks();
        });

        // initial state on load
        self.toggleBlocks();
    },

    registerDateTimePicker : function() {
        // Use native HTML5 datetime-local picker for delivery_time
        var field = jQuery('input[name="delivery_time"].dateTimeField');
        if (field.length) {
            field.attr('type', 'datetime-local');
        }
    },

    registerImagePreview: function() {
        // Preview for used_projects image upload (uitype 69)
        jQuery(document).on('change', 'input[name="used_projects[]"]', function (e) {
            var input = e.currentTarget;
            var $container = jQuery(input).closest('.fileUploadContainer').next('.ps-image-preview');

            if (!$container.length) {
                $container = jQuery('<div class="ps-image-preview" style="margin-top:10px;"></div>');
                jQuery(input).closest('.fileUploadContainer').after($container);
            }

            $container.empty();

            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (ev) {
                    var img = jQuery('<img />', {
                        src: ev.target.result,
                        style: 'max-width:400px;border-radius:8px;margin-top:5px;'
                    });
                    $container.append(img);
                };
                reader.readAsDataURL(input.files[0]);
            }
        });
    },

    registerEvents : function(){
        this._super();
        this.registerItemTypeEvent();
        this.registerDateTimePicker();
        this.registerImagePreview();
    }

});
