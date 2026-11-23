@php
    $user = auth()->user();
    $currentPanelId = \Filament\Facades\Filament::getCurrentPanel()?->getId();
    $isSuperAdmin = $currentPanelId === 'superadmin' || ($user && ($user->role?->value ?? '') === 'SUPER_ADMIN');
    $tenant = \Filament\Facades\Filament::getTenant();

    if ($isSuperAdmin) {
        $userName = $user?->name ?? 'Super Administrador';
        $userRole = 'Super Administrador';
        $tenantName = 'Nuvex Global SaaS';
        $avatarBg = '#00C975';
        $avatarShadow = 'rgba(0, 201, 117, 0.35)';
    } else {
        $userName = $user?->name ?? 'Paola Aguilera';
        $userRole = $user?->role instanceof \App\Enums\UserRole ? $user->role->label() : ($user?->role ?? 'Administradora');
        $tenantName = $tenant?->name ?? 'Cita Clave';
        $avatarBg = '#0D9488';
        $avatarShadow = 'rgba(13, 148, 136, 0.35)';
    }
    
    // Obtener iniciales
    $words = explode(' ', trim($userName));
    $initials = count($words) >= 2 
        ? strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1))
        : strtoupper(mb_substr($userName, 0, 2));

    $profileUrl = \Filament\Facades\Filament::getProfileUrl();
@endphp

<div class="nuvex-sidebar-profile">
    <a href="{{ $profileUrl }}" class="nuvex-profile-card-link" title="Ver y administrar mi perfil">
        <div class="nuvex-profile-avatar-wrap">
            <div class="nuvex-profile-avatar" style="background: {{ $avatarBg }}; box-shadow: 0 6px 16px -2px {{ $avatarShadow }};">
                {{ $initials }}
            </div>
            <span class="nuvex-profile-status-dot"></span>
        </div>

        <div class="nuvex-profile-info">
            <h4 class="nuvex-profile-name">{{ $userName }}</h4>
            <span class="nuvex-profile-role">{{ $userRole }} · {{ $tenantName }}</span>
        </div>
    </a>

    <a href="{{ $profileUrl }}" class="nuvex-profile-pill-btn" title="Editar mis datos y credenciales">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        <span>Mi Perfil</span>
    </a>

    <div class="nuvex-profile-divider"></div>
</div>

<style>
    .nuvex-sidebar-profile {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 1.25rem 1rem 0.85rem 1rem;
        margin-bottom: 0.5rem;
    }

    .nuvex-profile-card-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        width: 100%;
        cursor: pointer;
        transition: opacity 0.2s ease;
    }

    .nuvex-profile-card-link:hover {
        opacity: 0.92;
    }

    .nuvex-profile-avatar-wrap {
        position: relative;
        margin-bottom: 0.75rem;
    }

    .nuvex-profile-avatar {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 9999px;
        color: #FFFFFF;
        font-weight: 800;
        font-size: 1.15rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid rgba(255, 255, 255, 0.15);
        letter-spacing: 0.05em;
        transition: transform 0.2s ease;
    }

    .nuvex-profile-avatar:hover {
        transform: scale(1.05);
    }

    .nuvex-profile-status-dot {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 11px;
        height: 11px;
        background-color: #10B981;
        border-radius: 9999px;
        border: 2px solid #1E293B;
    }

    .nuvex-profile-info {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        width: 100%;
    }

    .nuvex-profile-name {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #FFFFFF;
        letter-spacing: -0.01em;
        line-height: 1.25;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .nuvex-profile-role {
        font-size: 0.72rem;
        font-weight: 500;
        color: #94A3B8;
        letter-spacing: 0.02em;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .nuvex-profile-pill-btn {
        margin-top: 10px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 9999px;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.14);
        color: #E2E8F0;
        font-size: 11px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    .nuvex-profile-pill-btn:hover {
        background: rgba(255, 255, 255, 0.18);
        color: #FFFFFF;
        border-color: rgba(255, 255, 255, 0.28);
        transform: translateY(-1px);
    }

    .nuvex-profile-pill-btn svg {
        width: 13px !important;
        height: 13px !important;
        min-width: 13px !important;
        min-height: 13px !important;
    }

    .nuvex-profile-divider {
        width: 80%;
        height: 1px;
        background: rgba(255, 255, 255, 0.12);
        margin-top: 1rem;
    }
</style>
