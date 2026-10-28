{{-- Menu Workspace di sidebar (desktop & mobile). Hanya tampil bila route workspace sudah terdaftar. --}}
@if ($wsEnabled)
    <a href="{{ route('workspace.index') }}" class="{{ request()->routeIs('workspace.*') ? 'bg-blue-50 border-r-4 border-blue-500 text-blue-700' : 'text-gray-600 hover:bg-gray-50' }} flex items-center px-4 py-3 text-sm font-medium">
        <i class="fas fa-layer-group w-5 mr-3"></i>
        Workspace
    </a>
@endif
