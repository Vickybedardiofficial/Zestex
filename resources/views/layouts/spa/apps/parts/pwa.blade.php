@if (config('pwa.enabled'))
	<link rel="manifest" href="{{ asset('pwa/manifest.json') }}">

	<script>
		if ('serviceWorker' in navigator) {
			window.addEventListener('load', () => {
				const isLocalhost = ['localhost', '127.0.0.1'].includes(window.location.hostname);

				if (isLocalhost) {
					navigator.serviceWorker.getRegistrations().then((registrations) => {
						registrations.forEach((registration) => registration.unregister());
					});
					return;
				}

				navigator.serviceWorker.register('/pwa/service-worker.js?v={{ $buildNumber ?? 1 }}')
					.then(() => {})
					.catch(() => {});
			});
		}
	</script>
@endif
