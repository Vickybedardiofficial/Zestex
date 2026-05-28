import { Howl, Howler } from 'howler';

const ZESTEXSounds = {
	sounds: embedder('config.sounds'),
	activeChatMessageReceived: function() {
		ZESTEXSounds.playSound(ZESTEXSounds.sounds.chat.active_chat_message_received);
	},
	backgroundChatMessageReceived: function() {
		ZESTEXSounds.playSound(ZESTEXSounds.sounds.chat.background_chat_message_received);
	},
	chatMessageSent: function() {
		ZESTEXSounds.playSound(ZESTEXSounds.sounds.chat.chat_message_sent);
	},
	notificationReceived: function() {
		ZESTEXSounds.playSound(ZESTEXSounds.sounds.notification.received);
	},
	uiFeedback: function() {
		ZESTEXSounds.playSound(ZESTEXSounds.sounds.notification.ui_feedback);
	},
	playSound: function(soundSourceUrl) {
		if(!soundSourceUrl) {
			return;
		}

		const audioContext = Howler.ctx;
		if(audioContext && audioContext.state === 'suspended') {
			audioContext.resume().catch(() => {});
		}

		let sound = new Howl({
			src: [soundSourceUrl],
			volume: 0.5
		});

		try {
			sound.play();
		}
		catch(error) {
			// Browser autoplay restrictions can block playback on first interaction.
		}
	}
};

export { ZESTEXSounds };
