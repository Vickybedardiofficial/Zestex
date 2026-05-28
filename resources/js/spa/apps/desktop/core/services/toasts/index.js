import useToastNotificationStore from '@D/store/toast/toast.store.js';
import { ZESTEXSounds } from '@/kernel/services/sounds/index.js';

const toastSuccess = (message = '', duration = 5000) => {
    const toastStore = useToastNotificationStore();
    toastStore.add(message, duration);
    
    try {
        ZESTEXSounds.uiFeedback();
    } catch (error) {
        console.log(error);
    }
};

const toastError = (message = '', duration = 5000) => {
    const toastStore = useToastNotificationStore();
    toastStore.add(message, duration, 'error');
    
    try {
        ZESTEXSounds.uiFeedback();
    } catch (error) {
        console.log(error);
    }
};

export { toastSuccess, toastError };