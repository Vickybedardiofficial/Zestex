/**
 * @description This component is used to copy the code to the clipboard.
 * @author Zestex Developer Vicky Bedardi Yadav
 * Naming convention: ZESTEXUI<ComponentName>
 * Example: ZESTEXUICode
 */

window.addEventListener('alpine:init', () => {
	Alpine.data('ZESTEXUICode', () => {
		return {
			copying: false,
			copy: async function() {
				this.copying = true;

				await navigator.clipboard.writeText(this.$refs.code.textContent);
				
				setTimeout(() => {
					this.copying = false;
				}, 1000);
			}
		}
	});
});