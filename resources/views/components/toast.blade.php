<div
    x-data="toastNotifications()"
    x-on:fiscal-toast.window="add($event.detail)"
    class="pointer-events-none fixed inset-x-4 top-24 z-50 flex flex-col items-end gap-3 sm:left-auto sm:w-full sm:max-w-md"
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

        window.fiscalToast = (type, message, title = '') => {
            window.dispatchEvent(new CustomEvent('fiscal-toast', { detail: { type, message, title } }));
        };
    });
</script>
