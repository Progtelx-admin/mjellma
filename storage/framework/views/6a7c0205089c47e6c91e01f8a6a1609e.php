<div class="modal fade" id="ai_text_generate" v-cloak tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo e(__("Magic text generator")); ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="" @submit="submit">
                    <div class="form-group">
                        <label class="control-label"><?php echo e(__("Keywords")); ?></label>
                        <div class="input-group mb-3">
                            <input
                                required
                                type="text"
                                v-model="message"
                                placeholder="<?php echo e(__("Some basic information or keywords")); ?>"
                                name="message"
                                class="form-control"
                            >
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit" id="button-addon2"><?php echo e(__('Generate')); ?>

                                    <i v-if="loading" class="fa fa-spinner fa-spin"></i>
                                    <i v-else class="fa fa-magic"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                <div
                    class="alert-text mt10"
                    v-show="apiStatus.content"
                    v-html="apiStatus.content"
                    :class="{'danger':!apiStatus.type,'success':apiStatus.type}"
                ></div>
                <div v-show="newMessage" v-html="newMessage" class="p-3 mt-2" style="background: #f8f9fa;max-height: 300px;overflow: auto"></div>
            </div>
            <div class="modal-footer" v-show="newMessage">
                <button type="button" @click="useContent" class="btn btn-success"><?php echo e(__("Use this content")); ?></button>
            </div>
        </div>
    </div>
</div>
<script>
    var ai_routes = {
        text: {
            url: '<?php echo e(route('ai.text.generate')); ?>',
        },
    };
</script>
<script src="<?php echo e(asset('module/ai/admin/text.generate.js?_v='.config('app.asset_version'))); ?>"></script>
<?php /**PATH /home/webdesig/amansite.com/pro/Ai/Views/frontend/text-generate.blade.php ENDPATH**/ ?>