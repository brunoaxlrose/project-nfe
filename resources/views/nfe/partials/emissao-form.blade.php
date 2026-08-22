@php($isModalMode = (bool) ($modalMode ?? false))
    <div
        x-data="formNota({ modal: @json($isModalMode) })"
        @keydown.alt.z.prevent="adicionarProduto"
        @abrir-formulario-nfe.window="inicializarModal"
        @resetar-formulario-nfe.window="if (modalMode) resetarFormulario()"
        class="w-full {{ $isModalMode ? '' : 'mx-auto max-w-[1800px]' }}"
    >
        @unless($isModalMode)
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="mb-2 text-sm font-medium text-blue-600">Fiscal / Nova NF-e</p>
                <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Nova nota fiscal</h1>
                <p class="mt-2 text-sm text-slate-500">Preencha a operação por etapas e revise tudo antes da transmissão.</p>
            </div>
            <div x-show="simulacaoHabilitada" x-cloak class="border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Homologação ativa: a emissão será simulada e não será transmitida à SEFAZ.</div>
        </div>
        @endunless

        <div class="grid items-start gap-6 2xl:grid-cols-[minmax(0,1fr)_350px]">
            <main class="min-w-0">
                <div class="mb-5 grid grid-cols-2 gap-2 md:grid-cols-5" aria-label="Etapas da emissão">
                    <template x-for="item in etapas" :key="item.id">
                        <button type="button" @click="irParaEtapa(item.id)" :disabled="item.id > etapa" class="flex items-center gap-2 border-b-2 px-2 py-3 text-left transition disabled:cursor-not-allowed disabled:opacity-40" :class="etapa === item.id ? 'border-blue-600 text-blue-700' : (etapa > item.id ? 'border-emerald-500 text-emerald-700' : 'border-slate-200 text-slate-400')">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center border text-xs font-bold" :class="etapa === item.id ? 'border-blue-600 bg-blue-50' : (etapa > item.id ? 'border-emerald-500 bg-emerald-50' : 'border-slate-200 bg-white')" x-text="etapa > item.id ? '✓' : item.id"></span>
                            <span class="min-w-0"><span class="block truncate text-xs font-semibold uppercase tracking-wide" x-text="item.titulo"></span><span class="mt-0.5 hidden truncate text-[11px] text-slate-500 lg:block" x-text="item.descricao"></span></span>
                        </button>
                    </template>
                </div>

                <form @submit.prevent="emitir" class="space-y-5">
                    <section x-show="etapa === 1" x-cloak class="card">
                        <div class="mb-5 flex items-start justify-between gap-4"><div><h2 class="section-title">Cabeçalho da operação</h2><p class="section-help">Defina a natureza e os parâmetros fiscais da nota.</p></div><span class="step-number">01</span></div>
                        <div class="grid gap-x-5 gap-y-4 md:grid-cols-2 xl:grid-cols-4">
                            <label><span class="label">Tipo de saída</span><select x-model="form.tipo_saida" class="field"><option value="propria">Emissão própria</option><option value="terceiros">Emissão por terceiros</option></select></label>
                            <label><span class="label">Série</span><input x-model.number="form.serie" @change="carregarNumero" type="number" min="1" max="999" class="field"></label>
                            <label><span class="label">Número</span><div class="relative"><input x-model.number="form.numero" type="number" min="1" required class="field pr-24"><span x-show="carregandoNumero" x-cloak class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-blue-600">Calculando...</span></div></label>
                            <label><span class="label">Finalidade</span><select x-model="form.finalidade" class="field"><option value="1">NF-e normal</option><option value="2">NF-e complementar</option><option value="3">NF-e de ajuste</option><option value="4">Devolução</option></select></label>
                            <label class="relative md:col-span-2 xl:col-span-3"><span class="label">Natureza da operação</span><input x-model="naturezaBusca" @input="filtrarNaturezas" @focus="mostrarNaturezas = true" @click.outside="mostrarNaturezas = false" required class="field" placeholder="Pesquise por descrição ou CFOP"><div x-show="mostrarNaturezas" x-cloak class="absolute z-30 mt-1 max-h-72 w-full overflow-y-auto border border-slate-200 bg-white p-1 shadow-xl"><template x-for="naturezaItem in naturezasFiltradas" :key="naturezaItem.id"><button type="button" @click="selecionarNatureza(naturezaItem)" class="block w-full px-3 py-2.5 text-left text-sm hover:bg-blue-50"><span class="font-medium text-slate-800" x-text="naturezaItem.descricao || naturezaItem.nome"></span><span class="ml-2 text-xs text-slate-500" x-text="'CFOP ' + naturezaItem.cfop_padrao + ' · ' + (naturezaItem.tipo_movimento || 'Saída')"></span></button></template><p x-show="!naturezasFiltradas.length" class="px-3 py-3 text-sm text-slate-500">Nenhuma natureza encontrada.</p></div><small x-show="natureza" class="mt-1 block text-xs text-blue-600" x-text="natureza ? 'Padrões: CFOP ' + natureza.cfop_padrao + ' · CSOSN ' + (natureza.csosn_padrao || 'não informado') : ''"></small></label>
                            <label><span class="label">Indicador de presença</span><select x-model.number="form.ind_pres" class="field"><option value="0">0 - Não se aplica</option><option value="1">1 - Presencial</option><option value="2">2 - Internet</option><option value="3">3 - Teleatendimento</option><option value="9">9 - Não presencial, outros</option></select></label>
                            <label><span class="label">Data de emissão</span><input x-model="form.data_emissao" type="date" required class="field"></label>
                            <label><span class="label">Hora de emissão</span><input x-model="form.hora_emissao" type="time" required class="field"></label>
                            <label><span class="label">Data de saída</span><input x-model="form.data_saida" type="date" class="field"></label>
                            <label><span class="label">Hora de saída</span><input x-model="form.hora_saida" type="time" class="field"></label>
                        </div>
                    </section>

                    <section x-show="etapa === 2" x-cloak class="card">
                        <div class="mb-5 flex items-start justify-between gap-4"><div><h2 class="section-title">Destinatário</h2><p class="section-help">Busque um cadastro existente ou preencha os dados para salvar localmente.</p></div><span class="step-number">02</span></div>
                        <div class="grid gap-x-5 gap-y-4 md:grid-cols-2">
                            <label class="relative md:col-span-2"><span class="label">Nome / Razão social</span><input x-model="cliente.razao_social" @input.debounce.400ms="buscarDestinatarios" @focus="mostrarDestinatarios = true" required class="field" placeholder="Digite o nome do destinatário"><div x-show="mostrarDestinatarios && destinatario.length" x-cloak class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto border border-slate-200 bg-white p-1 shadow-xl"><template x-for="item in destinatario" :key="item.origem + '-' + item.id"><button type="button" @click="selecionarDestinatario(item)" class="block w-full px-3 py-2 text-left text-sm hover:bg-blue-50"><span class="font-medium" x-text="item.razao_social || item.nome_razao_social"></span><span class="ml-2 text-xs text-slate-500" x-text="formatarDocumento(item.documento)"></span></button></template></div></label>
                            <label><span class="label">CPF / CNPJ</span>
                                <input x-model="cliente.documento"
                                       @input="formatarDocumentoInput($event); if(cliente.documento.replace(/\D/g, '').length === 14) consultarDocumento()"
                                       inputmode="numeric" required class="field" placeholder="Ex: 000.000.000-00 ou 00.000.000/0001-00">
                                <small x-show="consultandoCnpj" x-cloak class="mt-1 block text-xs text-blue-600">Consultando CNPJ...</small>
                            </label>
                            <label><span class="label">Inscrição Estadual</span><input x-model="cliente.ie" class="field" placeholder="Ex.: 110.042.490.114 ou ISENTO"></label>
                            <label><span class="label">CEP</span><input x-model="cliente.cep" @input.debounce.500ms="buscarCep" x-mask="99999-999" required class="field" placeholder="00000-000"><small x-show="consultandoCep" x-cloak class="mt-1 block text-xs text-blue-600">Consultando endereço...</small></label>
                            <label><span class="label">UF</span><input x-model="cliente.uf" maxlength="2" class="field" placeholder="Ex.: SP"></label>
                            <label><span class="label">Município</span><input x-model="cliente.cidade" required class="field" placeholder="Ex.: São Paulo"></label>
                            <label><span class="label">Bairro</span><input x-model="cliente.bairro" required class="field" placeholder="Ex.: Centro"></label>
                            <label class="md:col-span-2"><span class="label">Endereço</span><input x-model="cliente.logradouro" required class="field" placeholder="Ex.: Rua das Flores"></label>
                            <label><span class="label">Número</span><input x-model="cliente.numero" required class="field" placeholder="Ex.: 123"></label>
                            <label><span class="label">Complemento</span><input x-model="cliente.complemento" class="field" placeholder="Ex.: Sala 2 (opcional)"></label>
                            <label><span class="label">Celular / telefone</span><input x-model="cliente.fone" @input="formatarTelefoneInput($event)" inputmode="tel" maxlength="15" class="field" placeholder="Ex.: (11) 99999-9999"></label>
                            <label><span class="label">E-mail</span><input x-model="cliente.email" type="email" class="field" placeholder="Ex.: contato@empresa.com.br"></label>
                            <label class="flex items-center gap-3 md:col-span-2"><input x-model="form.consumidor_final" type="checkbox" class="h-4 w-4 accent-blue-600"><span class="text-sm font-medium text-slate-700">Consumidor final</span></label>
                        </div>
                        <p x-show="cliente.id" x-cloak class="mt-5 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">Cadastro local selecionado. Os dados serão usados nesta emissão.</p>
                    </section>

                    <section x-show="etapa === 3" x-cloak class="card">
                        <div class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"><div><div class="flex items-start gap-4"><div><h2 class="section-title">Itens da nota fiscal</h2><p class="section-help">Digite o código ou a descrição para localizar produtos cadastrados.</p></div><span class="step-number">03</span></div></div><button type="button" @click="adicionarProduto" class="border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100">+ Adicionar outro item <span class="hidden sm:inline">(Alt+Z)</span></button></div>
                        <div class="overflow-visible border border-slate-200">
                            <div class="min-w-[1180px]">
                                <div class="grid grid-cols-[44px_minmax(230px,1.5fr)_110px_70px_100px_110px_115px_110px_110px_48px] gap-2 bg-slate-50 px-3 py-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500"><span>#</span><span>Produto / descrição</span><span>Código</span><span>UN</span><span>Quantidade</span><span>Preço un.</span><span>Preço total</span><span>NCM</span><span>CFOP</span><span></span></div>
                        <template x-for="(produto, index) in produtos" :key="produto.uid"><div class="relative grid grid-cols-[44px_minmax(230px,1.5fr)_110px_70px_100px_110px_115px_110px_110px_48px] items-start gap-2 border-t border-slate-200 px-3 py-3"><span class="flex h-9 w-8 items-center justify-center bg-slate-100 text-xs font-semibold text-slate-500" x-text="index + 1"></span><label class="relative"><span class="sr-only">Descrição</span><input x-model="produto.descricao" @input.debounce.400ms="buscarProdutos(produto)" required class="field mt-0" placeholder="Descrição do produto"><div x-show="produto.sugestoes.length" x-cloak class="absolute left-0 top-10 z-40 w-[360px] border border-slate-200 bg-white p-1 shadow-xl"><template x-for="sugestao in produto.sugestoes" :key="sugestao.id"><button type="button" @click="selecionarProduto(produto, sugestao)" class="block w-full px-3 py-2 text-left text-sm hover:bg-blue-50"><span class="font-medium" x-text="sugestao.descricao"></span><span class="ml-2 text-xs text-slate-500" x-text="sugestao.codigo"></span></button></template></div></label><input x-model="produto.codigo" required class="field mt-0" placeholder="Código"><input x-model="produto.unidade" required maxlength="6" class="field mt-0" placeholder="UN"><input x-model.number="produto.quantidade" @keydown="bloquearNaoInteiro" @input="normalizarQuantidade(produto)" type="number" min="1" step="1" inputmode="numeric" required class="field mt-0"><input x-model.number="produto.valor_unitario" type="number" min="0" step="0.01" required class="field mt-0"><span class="flex h-10 items-center text-sm font-semibold tabular-nums text-slate-800" x-text="money(totalProduto(produto))"></span><input x-model="produto.ncm" @input.debounce.500ms="validarNcm(produto)" maxlength="8" required class="field mt-0" :class="produto.ncmInvalido === true ? 'border-amber-500' : ''" placeholder="8 dígitos"><input x-model="produto.cfop" required maxlength="4" inputmode="numeric" class="field mt-0" placeholder="CFOP"><div class="flex h-10 items-center justify-center"><button type="button" @click="removerProduto(index)" :disabled="produtos.length === 1" class="text-lg text-red-500 hover:text-red-700 disabled:cursor-not-allowed disabled:opacity-30" title="Remover item">⌫</button></div><small x-show="produto.ncmInvalido === true" x-cloak class="absolute left-[920px] top-[3.6rem] text-[11px] text-amber-600">NCM não localizado. Confirme.</small></div></template>
                            </div>
                        </div>
                        <div class="mt-4 flex flex-col gap-4 border-t border-slate-200 pt-4 lg:flex-row lg:items-end lg:justify-between"><label class="flex items-center gap-3"><input x-model="form.calculo_automatico" type="checkbox" class="h-4 w-4 accent-blue-600"><span class="text-sm font-medium text-slate-700">Cálculo automático ligado</span></label><div class="grid gap-3 sm:grid-cols-3"><div><span class="label">Total dos produtos</span><p class="mt-2 text-sm font-semibold" x-text="money(totalProdutos)"></p></div><label><span class="label">Desconto (R$)</span><input :value="formatarValor(totais.desconto)" @focus="$event.target.select()" @input="formatarDesconto($event)" :readonly="!form.calculo_automatico" type="text" inputmode="decimal" class="field" placeholder="0,00"></label><label><span class="label">Outras despesas (R$)</span><input x-model.number="totais.outras_despesas" :readonly="!form.calculo_automatico" type="number" min="0" step="0.01" class="field"></label></div></div>
                        <div class="mt-4 flex items-end justify-end border-t border-slate-200 pt-4"><div class="text-right"><span class="label">Total da nota</span><p class="mt-1 text-2xl font-bold tabular-nums text-slate-950" x-text="money(totalNota)"></p></div></div>
                    </section>

                    <section x-show="etapa === 4" x-cloak class="space-y-5">
                        <div class="card"><div class="mb-5 flex items-start justify-between gap-4"><div><h2 class="section-title">Transporte</h2><p class="section-help">Informe a modalidade de frete e os dados da transportadora, quando houver.</p></div><span class="step-number">04</span></div><div class="grid gap-x-5 gap-y-4 md:grid-cols-2 xl:grid-cols-4"><label><span class="label">Modalidade do frete</span><select x-model="transportadora.modalidade_frete" class="field"><option value="9">Sem frete</option><option value="0">Por conta do emitente</option><option value="1">Por conta do destinatário</option><option value="2">Por conta de terceiros</option></select></label><label><span class="label">Nome da transportadora</span><input x-model="transportadora.nome" class="field"></label><label><span class="label">CNPJ / CPF</span><input x-model="transportadora.documento" class="field"></label><label><span class="label">Inscrição Estadual</span><input x-model="transportadora.inscricao_estadual" class="field"></label><label class="md:col-span-2"><span class="label">Endereço</span><input x-model="transportadora.endereco" class="field"></label><label><span class="label">Município</span><input x-model="transportadora.municipio" class="field"></label><label><span class="label">Placa do veículo</span><input x-model="transportadora.placa" maxlength="8" class="field"></label><label><span class="label">UF do veículo</span><input x-model="transportadora.uf_veiculo" maxlength="2" class="field"></label><label><span class="label">RNTRC</span><input x-model="transportadora.rntc" class="field"></label></div></div>
                        <div class="card"><h2 class="section-title">Volumes</h2><p class="section-help">Use os campos abaixo quando a operação possuir transporte de volumes.</p><div class="grid gap-x-5 gap-y-4 md:grid-cols-2 xl:grid-cols-5"><label><span class="label">Quantidade</span><input x-model.number="volumes.quantidade" type="number" min="0" class="field"></label><label><span class="label">Espécie</span><input x-model="volumes.especie" class="field" placeholder="Caixa, pacote"></label><label><span class="label">Marca</span><input x-model="volumes.marca" class="field"></label><label><span class="label">Peso bruto (kg)</span><input x-model.number="volumes.peso_bruto" type="number" min="0" step="0.001" class="field"></label><label><span class="label">Peso líquido (kg)</span><input x-model.number="volumes.peso_liquido" type="number" min="0" step="0.001" class="field"></label></div></div>
                    </section>

                    <section x-show="etapa === 5" x-cloak class="space-y-5">
                        <div class="card"><div class="mb-5 flex items-start justify-between gap-4"><div><h2 class="section-title">Revisão da emissão</h2><p class="section-help">Confira os dados fiscais antes de transmitir ou simular a NF-e.</p></div><span class="step-number">05</span></div><div class="grid gap-5 text-sm md:grid-cols-2"><div><span class="label">Número / série</span><p class="mt-1 font-semibold" x-text="String(form.numero).padStart(6, '0') + ' / ' + form.serie"></p></div><div><span class="label">Finalidade</span><p class="mt-1 font-semibold" x-text="finalidadeLabel"></p></div><div><span class="label">Natureza</span><p class="mt-1 font-semibold" x-text="natureza ? (natureza.descricao || natureza.nome) : 'Não selecionada'"></p><p class="mt-1 text-xs text-slate-500" x-text="natureza ? 'CFOP ' + natureza.cfop_padrao + ' · CSOSN ' + (natureza.csosn_padrao || '—') : ''"></p></div><div><span class="label">Destinatário</span><p class="mt-1 font-semibold" x-text="cliente.razao_social || 'Não informado'"></p><p class="mt-1 text-xs text-slate-500" x-text="formatarDocumento(cliente.documento)"></p></div></div><div class="mt-6 overflow-x-auto border-t border-slate-100 pt-4"><table class="w-full min-w-[600px] text-sm"><thead><tr class="text-left text-xs uppercase text-slate-500"><th class="pb-2">Item</th><th class="pb-2">NCM</th><th class="pb-2">Quantidade</th><th class="pb-2">Unitário</th><th class="pb-2 text-right">Total</th></tr></thead><tbody><template x-for="produto in produtos" :key="produto.uid"><tr class="border-t border-slate-100"><td class="py-3" x-text="produto.descricao || 'Produto sem descrição'"></td><td class="py-3" x-text="produto.ncm || '—'"></td><td class="py-3" x-text="produto.quantidade"></td><td class="py-3" x-text="money(produto.valor_unitario)"></td><td class="py-3 text-right font-semibold" x-text="money(totalProduto(produto))"></td></tr></template></tbody></table></div></div>
                        <div class="card"><div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-100 pb-4"><div class="grid gap-4 sm:grid-cols-3"><div><span class="label">Total dos produtos</span><p class="mt-1 font-semibold" x-text="money(totalProdutos)"></p></div><div><span class="label">Desconto</span><p class="mt-1 font-semibold text-amber-700" x-text="money(totais.desconto)"></p></div><div><span class="label">Total da NF-e</span><p class="mt-1 text-lg font-bold" x-text="money(totalNota)"></p></div></div><button type="button" @click="mostrarPrevia = true" class="border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Visualizar prévia sem valor fiscal</button></div><label class="mt-5 block"><span class="label">Informações complementares</span><textarea x-model="informacoesComplementares" rows="4" maxlength="2000" class="field" placeholder="Ex.: Remessa para industrialização - OP 123."></textarea></label></div>
                    </section>

                    <div x-show="mensagem" x-cloak class="border px-4 py-3 text-sm" :class="sucesso ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'" x-text="mensagem"></div>
                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 pt-5"><button type="button" @click="voltar" x-show="etapa > 1" x-cloak class="border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Voltar</button><span x-show="etapa === 1" x-cloak></span><button type="button" @click="avancar" x-show="etapa < 5" x-cloak class="ml-auto bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">Continuar <span aria-hidden="true">→</span></button><div x-show="etapa === 5" x-cloak class="ml-auto flex flex-wrap justify-end gap-3"><button type="button" @click="salvarPendente" :disabled="isLoading" class="border border-amber-400 bg-amber-50 px-5 py-3 text-sm font-semibold text-amber-800 hover:bg-amber-100 disabled:opacity-60">Salvar como pendente</button><button type="submit" :disabled="isLoading" class="inline-flex items-center gap-2 bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"><span x-show="isLoading" x-cloak class="h-4 w-4 animate-spin rounded-full border-2 border-blue-200 border-t-white"></span><span x-text="isLoading ? (simulacaoHabilitada ? 'Simulando emissão...' : 'Enviando para a SEFAZ...') : (simulacaoHabilitada ? 'Simular emissão' : 'Enviar para a SEFAZ')"></span></button></div></div>
        </form>
        <div x-show="mostrarPrevia" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4" @keydown.escape.window="mostrarPrevia = false"><div class="max-h-[90vh] w-full max-w-5xl overflow-auto bg-white p-6 shadow-2xl"><div class="flex items-start justify-between border-b-2 border-slate-900 pb-3"><div><p class="text-xs font-bold uppercase tracking-widest text-slate-500">Prévia local</p><h2 class="text-2xl font-bold">DANFE — Documento sem valor fiscal</h2><p class="text-sm text-slate-500">Esta prévia não transmite nem autoriza a NF-e na SEFAZ.</p></div><button type="button" @click="mostrarPrevia = false" class="text-2xl text-slate-500">×</button></div><div class="mt-5 grid gap-3 border border-slate-800 p-3 text-sm sm:grid-cols-3"><div class="sm:col-span-2"><b>Emitente</b><p>NF-e <span x-text="String(form.numero).padStart(6, '0')"></span> · Série <span x-text="form.serie"></span></p><p>53.515.530 LUIZA MARINA TABORDA IAZZETTO</p></div><div><b>Destinatário</b><p x-text="cliente.razao_social"></p><p x-text="formatarDocumento(cliente.documento)"></p></div></div><div class="mt-4 overflow-x-auto border border-slate-800"><table class="w-full text-xs"><thead class="border-b border-slate-800"><tr><th class="p-2 text-left">Código</th><th class="p-2 text-left">Descrição</th><th class="p-2">Qtd.</th><th class="p-2">Unitário</th><th class="p-2">Total</th></tr></thead><tbody><template x-for="produto in produtos" :key="produto.uid"><tr class="border-b border-slate-300"><td class="p-2" x-text="produto.codigo"></td><td class="p-2" x-text="produto.descricao"></td><td class="p-2 text-center" x-text="produto.quantidade"></td><td class="p-2 text-right" x-text="money(produto.valor_unitario)"></td><td class="p-2 text-right" x-text="money(totalProduto(produto))"></td></tr></template></tbody></table></div><div class="mt-4 ml-auto max-w-sm space-y-1 border border-slate-800 p-3 text-right text-sm"><p>Total produtos: <b x-text="money(totalProdutos)"></b></p><p>Desconto: <b x-text="money(totais.desconto)"></b></p><p class="text-lg">Total da NF-e: <b x-text="money(totalNota)"></b></p></div></div></div>
            </main>

            <aside class="2xl:sticky 2xl:top-24"><div class="border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-start justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Resumo da emissão</p><h2 class="mt-1 text-lg font-bold text-slate-900" x-text="etapas[etapa - 1].titulo"></h2></div><span class="text-2xl font-bold text-slate-200" x-text="'0' + etapa"></span></div><div class="mt-5 space-y-4 text-sm"><div class="border-l-2 border-blue-500 pl-3"><p class="text-xs text-slate-500">Operação</p><p class="mt-1 font-semibold text-slate-800" x-text="natureza ? (natureza.descricao || natureza.nome) : 'Natureza não selecionada'"></p><p class="mt-1 text-xs text-slate-500" x-text="form.numero ? 'NF ' + String(form.numero).padStart(6, '0') + ' · Série ' + form.serie : 'Número calculado automaticamente'"></p></div><div class="border-l-2 border-slate-200 pl-3"><p class="text-xs text-slate-500">Destinatário</p><p class="mt-1 font-semibold text-slate-800" x-text="cliente.razao_social || 'Aguardando preenchimento'"></p><p class="mt-1 text-xs text-slate-500" x-text="formatarDocumento(cliente.documento) || 'CPF ou CNPJ não informado'"></p></div><div class="border-l-2 border-slate-200 pl-3"><p class="text-xs text-slate-500">Itens</p><p class="mt-1 font-semibold text-slate-800" x-text="produtos.length + ' item(ns)'"></p><p class="mt-1 text-base font-bold text-slate-950" x-text="money(totalNota)"></p></div><div class="border-l-2 border-slate-200 pl-3"><p class="text-xs text-slate-500">Transporte</p><p class="mt-1 font-semibold text-slate-800" x-text="modalidadeFreteLabel"></p></div></div><div class="mt-6 border-t border-slate-100 pt-4"><div class="flex items-center justify-between text-xs text-slate-500"><span>Progresso</span><span x-text="etapa + ' de ' + etapas.length"></span></div><div class="mt-2 h-1.5 bg-slate-100"><div class="h-1.5 bg-blue-600 transition-all" :style="'width: ' + ((etapa / etapas.length) * 100) + '%'" aria-hidden="true"></div></div></div></div></aside>
        </div>
    </div>

    <script>
        if (!window.fiscalFormNotaRegistered) {
            window.fiscalFormNotaRegistered = true;
            window.registerFiscalAlpine((Alpine) => {
                Alpine.data('formNota', (options = {}) => ({
                modalMode: Boolean(options.modal),
                etapa: 1,
                etapas: [{ id: 1, titulo: 'Operação', descricao: 'Cabeçalho fiscal' }, { id: 2, titulo: 'Destinatário', descricao: 'Cliente ou parceiro' }, { id: 3, titulo: 'Itens', descricao: 'produto da nota' }, { id: 4, titulo: 'Transporte', descricao: 'Frete e volumes' }, { id: 5, titulo: 'Revisão', descricao: 'Conferência final' }],
                form: { tipo_saida: 'propria', serie: 1, numero: '', id_natureza_operacao: '', finalidade: '1', ind_pres: 9, consumidor_final: true, data_emissao: '', hora_emissao: '', data_saida: '', hora_saida: '', calculo_automatico: true },
                cliente: { id: null, origem: 'cliente', razao_social: '', documento: '', ie: '', cep: '', logradouro: '', numero: '', complemento: '', bairro: '', cidade: '', uf: '', codigo_ibge: '', fone: '', email: '' },
                destinatario: [],
                mostrarDestinatarios: false,
                naturezas: [],
                naturezasFiltradas: [],
                naturezaBusca: '',
                mostrarNaturezas: false,
                carregandoNaturezas: false,
                carregandoNumero: false,
                produtos: [],
                totais: { desconto: 0, outras_despesas: 0 },
                transportadora: { modalidade_frete: '9', nome: '', documento: '', inscricao_estadual: '', endereco: '', municipio: '', placa: '', uf_veiculo: '', rntc: '' },
                volumes: { quantidade: 0, especie: '', marca: '', peso_bruto: 0, peso_liquido: 0 },
                informacoesComplementares: '',
                mostrarPrevia: false,
                consultandoCnpj: false,
                consultandoCep: false,
                isLoading: false,
                simulacaoHabilitada: @json((bool) config('nfe.simulate')),
                sucesso: false,
                mensagem: '',
                errors: {},
                initialized: false,
                destinatarioBuscaId: 0,

                get natureza() { return this.naturezas.find((item) => String(item.id) === String(this.form.id_natureza_operacao)); },
                get totalProdutos() { return this.produtos.reduce((total, produto) => total + this.totalProduto(produto), 0); },
                get totalNota() { return Math.max(0, this.totalProdutos - (Number(this.totais.desconto) || 0) + (Number(this.totais.outras_despesas) || 0)); },
                get finalidadeLabel() { return ({ 1: 'NF-e normal', 2: 'NF-e complementar', 3: 'NF-e de ajuste', 4: 'Devolução' }[this.form.finalidade] || 'NF-e normal'); },
                get modalidadeFreteLabel() { return ({ 0: 'Por conta do emitente', 1: 'Por conta do destinatário', 2: 'Por conta de terceiros', 9: 'Sem frete' }[this.transportadora.modalidade_frete] || 'Sem frete'); },

                async init() {
                    if (this.initialized) {
                        return;
                    }

                    this.initialized = true;
                    if (this.modalMode) {
                        return;
                    }

                    this.resetarFormulario();
                    await this.carregarNaturezas();
                    this.aplicarRascunho();
                    await this.carregarNumero();
                },

                async inicializarModal() {
                    if (!this.modalMode || this.isLoading) {
                        return;
                    }

                    this.resetarFormulario();

                    if (!this.naturezas.length) {
                        await this.carregarNaturezas();
                    } else {
                        this.naturezasFiltradas = this.naturezas;
                    }

                    this.aplicarRascunho();
                    await this.carregarNumero();
                },

                resetarFormulario() {
                    const agora = new Date();
                    this.etapa = 1;
                    this.form = { tipo_saida: 'propria', serie: 1, numero: '', id_natureza_operacao: '', finalidade: '1', ind_pres: 9, consumidor_final: true, data_emissao: '', hora_emissao: '', data_saida: '', hora_saida: '', calculo_automatico: true };
                    this.cliente = { id: null, origem: 'cliente', razao_social: '', documento: '', ie: '', cep: '', logradouro: '', numero: '', complemento: '', bairro: '', cidade: '', uf: '', codigo_ibge: '', fone: '', email: '' };
                    this.destinatario = [];
                    this.mostrarDestinatarios = false;
                    this.naturezaBusca = '';
                    this.naturezasFiltradas = this.naturezas;
                    this.mostrarNaturezas = false;
                    this.totais = { desconto: 0, outras_despesas: 0 };
                    this.transportadora = { modalidade_frete: '9', nome: '', documento: '', inscricao_estadual: '', endereco: '', municipio: '', placa: '', uf_veiculo: '', rntc: '' };
                    this.volumes = { quantidade: 0, especie: '', marca: '', peso_bruto: 0, peso_liquido: 0 };
                    this.informacoesComplementares = '';
                    this.mostrarPrevia = false;
                    this.sucesso = false;
                    this.mensagem = '';
                    this.errors = {};
                    this.form.data_emissao = this.dataIso(agora);
                    this.form.data_saida = this.form.data_emissao;
                    this.form.hora_emissao = this.timeLocal(agora);
                    this.form.hora_saida = this.form.hora_emissao;
                    this.produtos = [this.novoProduto()];
                },

                dataIso(date) { const offset = date.getTimezoneOffset(); return new Date(date.getTime() - (offset * 60000)).toISOString().slice(0, 10); },
                timeLocal(date) { return date.toTimeString().slice(0, 5); },
                headers() { return { Authorization: 'Bearer ' + localStorage.getItem('nfe_token'), Accept: 'application/json' }; },
                money(value) { return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value) || 0); },
                totalProduto(produto) { return (Number(produto.quantidade) || 0) * (Number(produto.valor_unitario) || 0); },
                bloquearNaoInteiro(event) { if (['e', 'E', '+', '-', '.', ','].includes(event.key)) event.preventDefault(); },
                normalizarQuantidade(produto) { const valor = Number(produto.quantidade); produto.quantidade = Number.isFinite(valor) ? Math.max(1, Math.trunc(valor)) : 1; },
                formatarValor(value) { return new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(value) || 0); },
                formatarDesconto(event) { const digits = String(event.target.value || '').replace(/\D/g, ''); const total = Number(this.totalProdutos.toFixed(2)); const valor = Math.min(Number(digits || 0) / 100, total); this.totais.desconto = Math.max(0, valor); event.target.value = this.formatarValor(this.totais.desconto); },
                novoProduto() { return { uid: Date.now() + Math.random(), codigo: '', descricao: '', ncm: '', quantidade: 1, valor_unitario: 0, unidade: 'UN', cfop: '', csosn: '', sugestoes: [], ncmInvalido: false }; },
                formatarDocumento(value) { const digits = String(value || '').replace(/\D/g, '').slice(0, 14); if (digits.length > 11) return digits.replace(/^(\d{2})(\d)/, '$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3').replace(/^(\d{2})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3/$4').replace(/^(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d)/, '$1.$2.$3/$4-$5'); return digits.replace(/^(\d{3})(\d)/, '$1.$2').replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3').replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4'); },
                formatarDocumentoInput(event) { this.cliente.documento = this.formatarDocumento(event.target.value); this.cliente.id = null; },
                formatarTelefone(value) { const digits = String(value || '').replace(/\D/g, '').slice(0, 11); if (digits.length <= 2) return digits.length ? '(' + digits : ''; if (digits.length <= 6) return '(' + digits.slice(0, 2) + ') ' + digits.slice(2); if (digits.length <= 10) return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 6) + '-' + digits.slice(6); return '(' + digits.slice(0, 2) + ') ' + digits.slice(2, 7) + '-' + digits.slice(7); },
                formatarTelefoneInput(event) { this.cliente.fone = this.formatarTelefone(event.target.value); },
                normalizarBusca(value) { return String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); },
                async parse(response) { const data = await response.json().catch(() => ({})); if (!response.ok) { const error = new Error(data.message || 'Não foi possível concluir a operação.'); error.status = response.status; error.errors = data.errors || {}; throw error; } return data; },
                notify(type, message, title) { window.fiscalToast?.(type, message, title); },
                friendlyError(error) { const message = String(error?.message || 'Não foi possível concluir a emissão.'); return /SQLSTATE|SQL:|Connection:|Stack trace|vendor\/|Illuminate/i.test(message) ? 'Não foi possível concluir a emissão agora. Nenhum documento foi transmitido. Tente novamente.' : message; },

                async carregarNaturezas() { if (this.carregandoNaturezas) return; this.carregandoNaturezas = true; try { const response = await fiscalFetch('/api/naturezas-operacao', { headers: this.headers() }); this.naturezas = await this.parse(response); this.naturezasFiltradas = this.naturezas; } catch (error) { this.notify('error', error.message, 'Naturezas indisponíveis'); } finally { this.carregandoNaturezas = false; } },
                filtrarNaturezas() { const termo = this.naturezaBusca.trim().toLowerCase(); this.naturezasFiltradas = this.naturezas.filter((item) => !termo || String(item.descricao || item.nome).toLowerCase().includes(termo) || String(item.cfop_padrao).includes(termo)); this.mostrarNaturezas = true; },
                selecionarNatureza(item) { this.form.id_natureza_operacao = item.id; this.naturezaBusca = item.descricao || item.nome; this.mostrarNaturezas = false; this.produtos.forEach((produto) => { produto.cfop = item.cfop_padrao || produto.cfop; produto.csosn = item.csosn_padrao || produto.csosn; }); },
                async carregarNumero() { if (this.carregandoNumero) return; this.carregandoNumero = true; try { const response = await fiscalFetch('/api/nfe/proximo-numero?serie=' + encodeURIComponent(this.form.serie || 1), { headers: this.headers() }); const data = await this.parse(response); this.form.numero = data.numero; } catch (error) { this.notify('warning', 'Não foi possível calcular o próximo número. Informe-o manualmente.', 'Numeração da série'); } finally { this.carregandoNumero = false; } },

                async buscarDestinatarios() { const termo = this.cliente.razao_social.trim(); const buscaId = ++this.destinatarioBuscaId; if (termo.length < 2) { this.destinatario = []; this.mostrarDestinatarios = false; return; } try { const [clientesResponse, destinatariosResponse] = await Promise.all([fiscalFetch('/api/clientes/buscar?q=' + encodeURIComponent(termo), { headers: this.headers() }), fiscalFetch('/api/destinatarios/buscar?q=' + encodeURIComponent(termo), { headers: this.headers() })]); if (buscaId !== this.destinatarioBuscaId || this.cliente.razao_social.trim() !== termo) return; const cliente = clientesResponse.ok ? (await clientesResponse.json()).map((item) => ({ ...item, origem: 'cliente' })) : []; const parceiros = destinatariosResponse.ok ? (await destinatariosResponse.json()).map((item) => ({ ...item, origem: 'destinatario' })) : []; const termoNormalizado = this.normalizarBusca(termo); const termoDocumento = termo.replace(/\D/g, ''); const vistos = new Set(); this.destinatario = [...cliente, ...parceiros].filter((item) => { const nome = this.normalizarBusca(item.razao_social || item.nome_razao_social); const documento = String(item.documento || '').replace(/\D/g, ''); const corresponde = nome.includes(termoNormalizado) || (termoDocumento.length >= 3 && documento.includes(termoDocumento)); const chave = item.origem + ':' + item.id; if (!corresponde || vistos.has(chave)) return false; vistos.add(chave); return true; }).sort((a, b) => { const aNome = this.normalizarBusca(a.razao_social || a.nome_razao_social); const bNome = this.normalizarBusca(b.razao_social || b.nome_razao_social); return Number(!aNome.startsWith(termoNormalizado)) - Number(!bNome.startsWith(termoNormalizado)) || aNome.localeCompare(bNome); }).slice(0, 10); this.mostrarDestinatarios = true; } catch (error) { if (!error.authExpired && buscaId === this.destinatarioBuscaId) this.notify('warning', 'A busca local está indisponível. Preencha o destinatário manualmente.', 'Busca local'); } },
                selecionarDestinatario(item) { this.cliente = { id: item.id, origem: item.origem, razao_social: item.razao_social || item.nome_razao_social, documento: this.formatarDocumento(item.documento), ie: item.inscricao_estadual || '', cep: item.cep || '', logradouro: item.logradouro || '', numero: item.numero || '', complemento: item.complemento || '', bairro: item.bairro || '', cidade: item.cidade || item.municipio || '', uf: item.uf || '', codigo_ibge: item.codigo_ibge || item.codigo_municipio_ibge || '', fone: this.formatarTelefone(item.fone || ''), email: item.email || '' }; this.destinatario = []; this.mostrarDestinatarios = false; },
                async consultarDocumento() { const documento = this.cliente.documento.replace(/\D/g, ''); if (documento.length < 11) return; try { const localResponse = await fiscalFetch('/api/clientes/buscar?q=' + encodeURIComponent(documento), { headers: this.headers() }); if (localResponse.ok) { const locais = await localResponse.json(); const exato = locais.find((item) => String(item.documento).replace(/\D/g, '') === documento); if (exato) { this.selecionarDestinatario({ ...exato, origem: 'cliente' }); return; } } } catch (error) { if (error.authExpired) return; } if (documento.length !== 14) return; this.consultandoCnpj = true; try { const response = await fetch('https://brasilapi.com.br/api/cnpj/v1/' + documento); if (response.status === 429) throw new Error('A consulta pública atingiu o limite temporário. Preencha os dados manualmente.'); if (!response.ok) throw new Error('CNPJ não encontrado. Preencha os dados manualmente.'); const data = await response.json(); Object.assign(this.cliente, { documento: this.formatarDocumento(documento), razao_social: data.razao_social || data.nome_fantasia || '', cep: data.cep || '', logradouro: data.logradouro || '', numero: data.numero || '', bairro: data.bairro || '', cidade: data.municipio || '', uf: data.uf || '', ie: data.inscricao_estadual || '' }); await this.salvarClienteLocal(); this.notify('success', 'Dados do CNPJ preenchidos e salvos no cadastro local.', 'Destinatário atualizado'); } catch (error) { this.notify('warning', error.message, 'Consulta automática indisponível'); } finally { this.consultandoCnpj = false; } },
                async buscarCep() { const cep = this.cliente.cep.replace(/\D/g, ''); if (cep.length !== 8) return; this.consultandoCep = true; try { const response = await fetch('https://brasilapi.com.br/api/cep/v1/' + cep); if (response.status === 429) throw new Error('A consulta pública atingiu o limite temporário. Preencha o endereço manualmente.'); if (!response.ok) throw new Error('CEP não encontrado. Preencha o endereço manualmente.'); const data = await response.json(); Object.assign(this.cliente, { cep, logradouro: data.street || this.cliente.logradouro, bairro: data.neighborhood || this.cliente.bairro, cidade: data.city || this.cliente.cidade, uf: data.state || this.cliente.uf, codigo_ibge: data.city_ibge || this.cliente.codigo_ibge }); if (this.cliente.razao_social && this.cliente.documento) await this.salvarClienteLocal(); } catch (error) { this.notify('warning', error.message, 'Consulta de CEP indisponível'); } finally { this.consultandoCep = false; } },
                async salvarClienteLocal() { if (this.cliente.origem === 'destinatario' || !this.cliente.razao_social || this.cliente.documento.replace(/\D/g, '').length < 11) return; const response = await fiscalFetch('/api/clientes/importar', { method: 'POST', headers: { ...this.headers(), 'Content-Type': 'application/json' }, body: JSON.stringify({ ...this.cliente, documento: this.cliente.documento, inscricao_estadual: this.cliente.ie }) }); const data = await this.parse(response); this.selecionarDestinatario({ ...data, origem: 'cliente' }); },

                adicionarProduto() { const produto = this.novoProduto(); if (this.natureza) { produto.cfop = this.natureza.cfop_padrao || ''; produto.csosn = this.natureza.csosn_padrao || ''; } this.produtos.push(produto); },
                removerProduto(index) { if (this.produtos.length > 1) this.produtos.splice(index, 1); },
                async buscarProdutos(produto) { const termo = (produto.descricao || produto.codigo || '').trim(); produto.buscaId = (produto.buscaId || 0) + 1; const buscaId = produto.buscaId; if (termo.length < 2) { produto.sugestoes = []; return; } try { const response = await fiscalFetch('/api/produtos/buscar?q=' + encodeURIComponent(termo), { headers: this.headers() }); if (buscaId !== produto.buscaId || (produto.descricao || produto.codigo || '').trim() !== termo) return; const resultados = response.ok ? await response.json() : []; const termoNormalizado = this.normalizarBusca(termo); const termoCodigo = termo.replace(/\D/g, ''); produto.sugestoes = resultados.filter((item) => { const descricao = this.normalizarBusca(item.descricao); const codigo = String(item.codigo || '').toLowerCase(); return descricao.includes(termoNormalizado) || (termoCodigo.length >= 2 && codigo.includes(termo.toLowerCase())); }).slice(0, 10); } catch (error) { if (buscaId === produto.buscaId) produto.sugestoes = []; } },
                selecionarProduto(produto, item) { Object.assign(produto, { codigo: item.codigo, descricao: item.descricao, ncm: item.ncm, valor_unitario: Number(item.valor_unitario || 0), unidade: item.unidade || 'UN', cfop: this.natureza?.cfop_padrao || item.cfop || '', csosn: this.natureza?.csosn_padrao || item.csosn || '', sugestoes: [] }); },
                async validarNcm(produto) { const ncm = String(produto.ncm || '').replace(/\D/g, ''); if (ncm.length !== 8) { produto.ncmInvalido = false; return; } try { const response = await fetch('https://brasilapi.com.br/api/ncm/v1/' + ncm); produto.ncmInvalido = response.status === 429 ? null : !response.ok; } catch (error) { produto.ncmInvalido = null; } },

                irParaEtapa(numero) { if (numero <= this.etapa) this.etapa = numero; },
                async avancar() { if (!(await this.validarEtapa(this.etapa))) return; this.etapa += 1; },
                async validarEtapa(numero) { if (numero === 1 && !this.form.id_natureza_operacao) { this.notify('warning', 'Selecione a natureza da operação antes de continuar.', 'Operação obrigatória'); return false; } if (numero === 2) { const campos = ['razao_social', 'documento', 'cep', 'logradouro', 'numero', 'bairro', 'cidade', 'uf']; if (campos.some((campo) => !String(this.cliente[campo] || '').trim())) { this.notify('warning', 'Complete os dados obrigatórios do destinatário.', 'Dados incompletos'); return false; } if (!this.cliente.id) { try { await this.salvarClienteLocal(); } catch (error) { this.notify('warning', 'Salve ou selecione um destinatário antes de continuar.', 'Cadastro necessário'); return false; } } } if (numero === 3 && (!this.produtos.length || this.produtos.some((produto) => !produto.codigo || !produto.descricao || !produto.ncm || !produto.cfop || !produto.unidade || Number(produto.quantidade) <= 0))) { this.notify('warning', 'Preencha código, descrição, NCM, CFOP, unidade e quantidade dos itens.', 'Itens incompletos'); return false; } return true; },
                voltar() { if (this.etapa > 1 && !this.isLoading) this.etapa -= 1; },

                aplicarRascunho() { const bruto = sessionStorage.getItem('fiscalflow_clone_draft'); if (!bruto) return; sessionStorage.removeItem('fiscalflow_clone_draft'); try { const draft = JSON.parse(bruto); this.form.serie = draft.serie || this.form.serie; this.form.id_natureza_operacao = draft.id_natureza_operacao || ''; const naturezaSelecionada = this.naturezas.find((item) => String(item.id) === String(this.form.id_natureza_operacao)); this.naturezaBusca = naturezaSelecionada ? (naturezaSelecionada.descricao || naturezaSelecionada.nome) : ''; this.informacoesComplementares = draft.informacoes_complementares || ''; if (Array.isArray(draft.produtos) && draft.produtos.length) this.produtos = draft.produtos.map((produto) => ({ ...this.novoProduto(), ...produto, uid: Date.now() + Math.random(), sugestoes: [] })); const destinatario = draft.destinatario; if (destinatario) { this.cliente = { ...this.cliente, razao_social: destinatario.nome || '', documento: this.formatarDocumento(destinatario.cnpj || destinatario.cpf || ''), ie: destinatario.ie || '', cep: destinatario.endereco?.cep || '', logradouro: destinatario.endereco?.xLgr || '', numero: destinatario.endereco?.nro || '', bairro: destinatario.endereco?.xBairro || '', cidade: destinatario.endereco?.xMun || '', uf: destinatario.endereco?.uf || '', codigo_ibge: destinatario.endereco?.cMun || '' }; } this.etapa = 1; this.notify('success', 'Os dados da nota anterior foram carregados como rascunho.', 'Nota clonada'); } catch (error) { this.notify('warning', 'O rascunho não pôde ser carregado.', 'Clonagem'); } },

                montarPayload(modo = 'emitir') { return { modo, numero: Number(this.form.numero), serie: Number(this.form.serie), id_natureza_operacao: Number(this.form.id_natureza_operacao), id_cliente: this.cliente.origem === 'cliente' ? this.cliente.id : null, id_destinatario: this.cliente.origem === 'destinatario' ? this.cliente.id : null, tipo_saida: this.form.tipo_saida, data_emissao: this.form.data_emissao, hora_emissao: this.form.hora_emissao, data_saida: this.form.data_saida, hora_saida: this.form.hora_saida, finalidade: this.form.finalidade, ind_pres: Number(this.form.ind_pres), consumidor_final: this.form.consumidor_final, produtos: this.produtos, desconto: Number(this.totais.desconto || 0), outras_despesas: Number(this.totais.outras_despesas || 0), calculo_automatico: this.form.calculo_automatico, transportadora: this.transportadora, volumes: this.volumes, informacoes_complementares: this.informacoesComplementares, pagamento: { tpag: '90' } }; },

                async salvarPendente() {
                    if (this.isLoading || !(await this.validarEtapa(5)) || !this.cliente.id) { this.notify('warning', 'Complete a revisão e selecione o destinatário antes de salvar.', 'Dados obrigatórios'); return; }
                    this.isLoading = true; this.errors = {}; this.mensagem = '';
                    try {
                        const response = await fiscalFetch('/api/nfe', { method: 'POST', headers: { ...this.headers(), 'Content-Type': 'application/json' }, body: JSON.stringify(this.montarPayload('rascunho')) });
                        const data = await this.parse(response);
                        this.sucesso = true; this.mensagem = 'NF-e salva como pendente. Nenhum documento foi enviado à SEFAZ.';
                        this.notify('success', this.mensagem, 'Pendente salva');
                        this.resetarFormulario(); await this.carregarNumero();
                        if (this.modalMode) this.$dispatch('nfe-emitida', { nota: data, message: this.mensagem });
                    } catch (error) { this.sucesso = false; this.errors = error.errors || {}; this.mensagem = this.friendlyError(error); this.notify(error.status === 422 ? 'warning' : 'error', this.mensagem, 'Não foi possível salvar'); }
                    finally { this.isLoading = false; }
                },

                async emitir() {
                    if (this.isLoading) {
                        return;
                    }

                    if (!(await this.validarEtapa(5)) || !this.cliente.id) {
                        this.notify('warning', 'Selecione ou salve o destinatário antes de emitir.', 'Dados obrigatórios');
                        return;
                    }

                    this.isLoading = true;
                    this.errors = {};
                    this.mensagem = '';

                    if (this.modalMode) {
                        this.$dispatch('nfe-emissao-loading', { loading: true });
                    }

                    try {
                        const response = await fiscalFetch('/api/nfe', {
                            method: 'POST',
                            headers: { ...this.headers(), 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.montarPayload('emitir')),
                        });
                        const data = await this.parse(response);

                        this.sucesso = true;
                        this.mensagem = data.status === 'simulada'
                            ? 'Simulação concluída em homologação. Nenhum documento foi transmitido à SEFAZ.'
                            : (data.status === 'aguardando_retorno'
                                ? 'NF-e enviada para processamento. Acompanhe o retorno no histórico.'
                                : 'NF-e registrada com sucesso.');
                        this.notify(
                            data.status === 'simulada' ? 'warning' : 'success',
                            this.mensagem,
                            data.status === 'simulada' ? 'Simulação concluída' : 'Emissão registrada',
                        );

                        // Após registrar/transmitir a nota, inicia um novo
                        // formulário no primeiro passo. Em caso de rejeição,
                        // preservamos os dados para permitir a correção.
                        if (['autorizada', 'simulada', 'aguardando_retorno'].includes(data.status)) {
                            this.resetarFormulario();
                            await this.carregarNumero();
                        }

                        if (this.modalMode) {
                            this.$dispatch('nfe-emitida', { nota: data, message: this.mensagem });
                        }
                    } catch (error) {
                        this.sucesso = false;
                        this.errors = error.errors || {};
                        this.mensagem = this.friendlyError(error);
                        this.notify(
                            error.status === 422 ? 'warning' : 'error',
                            this.mensagem,
                            error.status === 422 ? 'Revise os campos' : 'Falha na emissão',
                        );
                    } finally {
                        this.isLoading = false;

                        if (this.modalMode) {
                            this.$dispatch('nfe-emissao-loading', { loading: false });
                        }
                    }
                },
                }));
            });
        }
    </script>
    <style>
        .card { border: 1px solid #e2e8f0; background: #fff; padding: 1.5rem; box-shadow: 0 1px 2px rgb(15 23 42 / .04); }
        .section-title { font-weight: 600; color: #0f172a; }
        .section-help { margin-top: .25rem; font-size: .875rem; color: #64748b; }
        .label { display: block; font-size: .75rem; font-weight: 600; color: #475569; }
        .field { margin-top: .5rem; display: block; width: 100%; border: 1px solid #cbd5e1; border-radius: 0; background: #fff; padding: .7rem .75rem; font-size: .875rem; box-shadow: none; }
        .field:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgb(37 99 235 / .12); outline: none; }
        .step-number { display: flex; height: 2.25rem; width: 2.25rem; flex-shrink: 0; align-items: center; justify-content: center; background: #eff6ff; color: #1d4ed8; font-size: .75rem; font-weight: 700; }
    </style>
