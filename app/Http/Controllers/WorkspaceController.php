<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function dashboard(Request $request): View|string
    {
        return $this->renderPage($request, 'dashboard');
    }

    public function nfeCreate(Request $request): View|string
    {
        return $this->renderPage($request, 'nfe.create');
    }

    public function nfeIndex(Request $request): View|string
    {
        return $this->renderPage($request, 'nfe.index', ['notas' => new Collection()]);
    }

    public function configuracoes(Request $request): View|string
    {
        return $this->renderPage($request, 'configuracoes');
    }

    public function usuarios(Request $request): View|string
    {
        return $this->renderPage($request, 'usuarios.index');
    }

    public function fornecedores(Request $request): View|string
    {
        return $this->renderPage($request, 'fornecedores.index');
    }

    public function clientes(Request $request): View|string
    {
        return $this->renderPage($request, 'clientes.index');
    }

    public function produtos(Request $request): View|string
    {
        return $this->renderPage($request, 'produtos.index');
    }

    private function renderPage(Request $request, string $view, array $data = []): View|string
    {
        if ($request->ajax()) {
            return view($view, [...$data, 'partial' => true])->render();
        }

        return view($view, $data);
    }
}
