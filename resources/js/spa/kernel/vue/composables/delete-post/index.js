import { ZESTEXEventBus } from '@/kernel/events/bus/index.js';
import { ZESTEXAPI } from '@/kernel/services/api-client/native/index.js';
import { useI18n } from 'vue-i18n';

function useDeletePost() {
    const { t } = useI18n();

	const postDeleter = (postData, callback = null) => {
		ZESTEXEventBus.emit('confirmation-modal:open', {
			title: t('prompt.delete_post.title'),
			description: t('prompt.delete_post.description'),
			onConfirm: () => {
				ZESTEXAPI().userTimeline().with({
					id: postData.id
				}).delete('post/delete').then(() => {

					// Call the callback if it is provided.
					if (callback) {
						callback(postData.id);
					}
	
				}).catch((error) => {
					if (error.response) {
						callback(postData.id);
						console.log(error.response.data.message);
					}
				});
			}
		});
	}

	return {
		postDeleter: postDeleter
	}
}

export { useDeletePost };