<div
    x-data="toastNotifications()"
    x-on:fiscal-toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-4 top-24 z-[120] flex flex-col items-end gap-3 sm:left-auto sm:w-full sm:max-w-md"
    aria-live="polite"
    aria-atomic="true"
>
    <template x-for="toast in items" :key="toast.id">
        <div
            x-show="toast.visible"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="translate-x-3 opacity-0"
            class="pointer-events-auto flex w-full items-start gap-3 rounded-xl border bg-white p-4 shadow-lg"
            :class="toast.type === 'success' ? 'border-emerald-200' : (toast.type === 'warning' ? 'border-amber-200' : 'border-red-200')"
            role="status"
        >
            <span
                class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                :class="toast.type === 'success' ? 'bg-emerald-100 text-emerald-700' : (toast.type === 'warning' ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700')"
                x-text="toast.type === 'success' ? '✓' : (toast.type === 'warning' ? '!' : '×')"
            ></span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-slate-900" x-text="toast.title"></p>
                <p x-show="toast.message" class="mt-1 text-sm text-slate-600" x-text="toast.message"></p>
            </div>
            <button type="button" @click="remove(toast.id)" class="text-lg leading-none text-slate-400 hover:text-slate-700" aria-label="Fechar notificação">×</button>
        </div>
    </template>
</div>

<div
    x-data="confirmDialog()"
    x-on:fiscal-confirm-open.window="open($event.detail)"
    x-cloak
    x-show="openState"
    x-transition.opacity
    class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/50 p-4"
>
    <div
        x-show="openState"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="scale-95 opacity-0"
        x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="scale-100 opacity-100"
        x-transition:leave-end="scale-95 opacity-0"
        @click.outside="cancel"
        class="w-full max-w-md rounded-xl border border-amber-200 bg-white p-5 shadow-2xl"
        role="dialog"
        aria-modal="true"
    >
        <div class="flex items-start gap-3">
            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-lg font-bold text-amber-700">!</span>
            <div class="min-w-0 flex-1">
                <p class="text-base font-semibold text-slate-900" x-text="title"></p>
                <p class="mt-2 text-sm leading-6 text-slate-600" x-text="message"></p>
            </div>
            <button type="button" @click="cancel" class="text-xl leading-none text-slate-400 hover:text-slate-700" aria-label="Fechar confirmação">×</button>
        </div>
        <div class="mt-5 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <button type="button" @click="cancel" class="border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" x-text="cancelText"></button>
            <button type="button" @click="confirm" class="bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700" x-text="confirmText"></button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('toastNotifications', () => ({
            items: [],
            nextId: 1,
            add(detail = {}) {
                const type = ['success', 'error', 'warning'].includes(detail.type) ? detail.type : 'error';
                const toast = {
                    id: this.nextId++,
                    type,
                    title: detail.title || (type === 'success' ? 'Operação concluída' : (type === 'warning' ? 'Atenção' : 'Não foi possível concluir')),
                    message: detail.message || '',
                    visible: true,
                };
                this.items.push(toast);
                window.setTimeout(() => this.remove(toast.id), 5000);
            },
            remove(id) {
                const toast = this.items.find((item) => item.id === id);
                if (toast) toast.visible = false;
                window.setTimeout(() => {
                    this.items = this.items.filter((item) => item.id !== id);
                }, 180);
            },
        }));

        Alpine.data('confirmDialog', () => ({
            openState: false,
            title: '',
            message: '',
            confirmText: 'Confirmar',
            cancelText: 'Cancelar',
            resolver: null,
            open(detail = {}) {
                this.title = detail.title || 'Confirmar operação';
                this.message = detail.message || 'Deseja continuar?';
                this.confirmText = detail.confirmText || 'Confirmar';
                this.cancelText = detail.cancelText || 'Cancelar';
                this.resolver = detail.resolve || null;
                this.openState = true;
            },
            close(value) {
                this.openState = false;
                if (this.resolver) this.resolver(value);
                this.resolver = null;
            },
            confirm() {
                this.close(true);
            },
            cancel() {
                this.close(false);
            },
        }));

        window.fiscalToast = (type, message, title = '') => {
            window.dispatchEvent(new CustomEvent('fiscal-toast', { detail: { type, message, title } }));
        };

        window.fiscalConfirm = (detail = {}) => new Promise((resolve) => {
            window.dispatchEvent(new CustomEvent('fiscal-confirm-open', { detail: { ...detail, resolve } }));
        });
    });
</script>
