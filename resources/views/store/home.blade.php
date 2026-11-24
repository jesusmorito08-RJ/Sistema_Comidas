@extends('layouts.app')

@section('title', 'Sazón — comida que llega al corazón')

@section('content')
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow"><span class="eyebrow-line"></span> SABOR CASERO, A TU PUERTA</span>
            <h1>Tu próxima comida<br>favorita <em>está aquí.</em></h1>
            <p>Recetas llenas de sabor, ingredientes frescos y ese toque casero que convierte cualquier día en uno especial.</p>
            <div class="hero-actions"><a class="button button-primary" href="#menu">Explorar el menú <span>↘</span></a><a class="text-link" href="{{ route('reservations.index') }}">¿Prefieres reservar? <span>→</span></a></div>
            <div class="hero-proof"><div class="avatar-stack"><span>M</span><span>A</span><span>J</span></div><div><strong>4.9/5</strong><small>Más de 1,200 clientes felices</small></div><span class="proof-star">★★★★★</span></div>
        </div>
        <div class="hero-visual">
            <div class="hero-image"></div>
            <div class="hero-sticker"><span>✦</span><strong>Fresco<br>y delicioso</strong><small>hecho hoy, para ti</small></div>
            <div class="hero-note"><span class="note-icon">↗</span><span><strong>Entrega rápida</strong><small>Tu cocina está a minutos</small></span></div>
        </div>
    </div>
</section>
<section class="benefits">
    <div class="container benefits-grid">
        <div><span class="benefit-icon">✿</span><span><strong>Ingredientes frescos</strong><small>Elegidos cada día</small></span></div>
        <div><span class="benefit-icon">⌁</span><span><strong>Entrega cuidadosa</strong><small>Caliente hasta tu puerta</small></span></div>
        <div><span class="benefit-icon">♡</span><span><strong>Hecho con cariño</strong><small>Como en casa</small></span></div>
        <div><span class="benefit-icon">✦</span><span><strong>Pago a tu manera</strong><small>Yape o Plin</small></span></div>
    </div>
</section>
<section class="section menu-section" id="menu">
    <div class="container menu-panel menu-book" data-menu-book data-active-tab="{{ $activeTabId }}">
        <header class="menu-book-heading">
            <span class="eyebrow"><span class="eyebrow-line"></span> SABORES DE SAZÓN</span>
            <h2>Nuestro menú</h2>
            <p>Elige una página y descubre platos preparados al momento.</p>
        </header>
        @if($menuPages->isNotEmpty())
            <nav class="menu-tabs" role="tablist" aria-label="Secciones del menú">
                @foreach($menuPages as $menuPage)
                    <a
                        id="tab-{{ $menuPage['id'] }}"
                        class="menu-tab"
                        href="#{{ $menuPage['id'] }}"
                        role="tab"
                        aria-controls="{{ $menuPage['id'] }}"
                        aria-selected="{{ $activeTabId === $menuPage['id'] ? 'true' : 'false' }}"
                        tabindex="{{ $activeTabId === $menuPage['id'] ? '0' : '-1' }}"
                        data-menu-tab="{{ $menuPage['id'] }}"
                    >{{ $menuPage['title'] }}</a>
                @endforeach
            </nav>
            <div class="menu-page-stage" data-menu-stage>
                @foreach($menuPages as $menuPage)
                    @php $isInitialPage = $menuPage['id'] === $activeTabId; @endphp
                    <section
                        id="{{ $menuPage['id'] }}"
                        class="menu-page{{ $menuPage['foods']->count() > 8 ? ' menu-page--dense' : '' }}{{ $menuPage['compact'] ? ' menu-page--compact' : '' }}"
                        role="tabpanel"
                        aria-labelledby="tab-{{ $menuPage['id'] }}"
                        tabindex="0"
                        data-menu-page="{{ $menuPage['id'] }}"
                    >
                        <header class="menu-page-heading">
                            <span class="eyebrow"><span class="eyebrow-line"></span> {{ $menuPage['eyebrow'] }}</span>
                            <h3>{{ $menuPage['title'] }}</h3>
                            <p>{{ $menuPage['description'] }}</p>
                        </header>
                        <div class="menu-dishes">
                            @foreach($menuPage['foods'] as $food)
                                @php
                                    $image = $food->image_path ? (filter_var($food->image_path, FILTER_VALIDATE_URL) ? $food->image_path : \Illuminate\Support\Facades\Storage::disk('public')->url($food->image_path)) : null;
                                    $photoMotion = $loop->odd ? 'from-right' : 'from-left';
                                    $textMotion = $loop->odd ? 'from-left' : 'from-right';
                                @endphp
                                <article class="menu-dish">
                                    <div class="menu-dish-copy" data-menu-reveal="{{ $textMotion }}">
                                        <span class="menu-dish-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <span class="menu-dish-category">{{ $food->category }}</span>
                                        <div class="menu-dish-badges">
                                            @if($food->is_featured)<span class="menu-badge menu-badge-favorite">FAVORITO</span>@endif
                                            @if($food->offer_price)<span class="menu-badge menu-badge-offer">OFERTA</span>@endif
                                        </div>
                                        <h4>{{ $food->name }}</h4>
                                        <p>{{ $food->description }}</p>
                                        <div class="menu-dish-bottom">
                                            <div class="price">
                                                @if($food->offer_price)<del>${{ number_format($food->price, 2) }}</del>@endif
                                                <strong>${{ number_format($food->offer_price ?? $food->price, 2) }}</strong>
                                            </div>
                                            <form method="POST" action="{{ route('cart.add', $food) }}">
                                                @csrf
                                                <button class="add-button" type="submit" aria-label="Agregar {{ $food->name }} al carrito">+</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="menu-dish-image" data-menu-reveal="{{ $photoMotion }}">
                                        @if($image)
                                            <img
                                                src="{{ $image }}"
                                                alt="{{ $food->name }}"
                                                @if($isInitialPage && $loop->first) loading="eager" fetchpriority="high" @else loading="lazy" @endif
                                                decoding="async"
                                                onerror="this.onerror=null;this.hidden=true;this.nextElementSibling.hidden=false"
                                            >
                                            <span class="menu-dish-image-placeholder" role="img" aria-label="Imagen no disponible para {{ $food->name }}" hidden>🍽️</span>
                                        @else
                                            <span class="menu-dish-image-placeholder" role="img" aria-label="Imagen no disponible para {{ $food->name }}">🍽️</span>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @else
            <div class="empty-state"><span>🍽️</span><h3>Estamos preparando el menú</h3><p>Muy pronto encontrarás aquí algo delicioso.</p></div>
        @endif
    </div>
</section>
<section class="offer-banner"><div class="container offer-content"><span class="offer-spark">✳</span><div><span class="eyebrow">UN PEQUEÑO DETALLE PARA TI</span><h2>Tu antojo de hoy merece algo especial.</h2><p>Descubre nuestras ofertas y encuentra tu nuevo favorito.</p></div><a class="button button-white" href="#menu">Ver el menú <span>→</span></a></div></section>
<section class="section reservation-cta"><div class="container reservation-card"><div><span class="eyebrow"><span class="eyebrow-line"></span> UN MOMENTO PARA COMPARTIR</span><h2>¿Una ocasión especial?</h2><p>Reserva tu horario y déjanos hacer que el momento sea aún más rico.</p></div><a class="button button-primary" href="{{ route('reservations.index') }}">Hacer una reserva <span>→</span></a></div></section>
<script>
(() => {
    const book = document.querySelector('[data-menu-book]');
    if (!book) {
        return;
    }

    const tabs = [...book.querySelectorAll('[data-menu-tab]')];
    const pages = [...book.querySelectorAll('[data-menu-page]')];
    const stage = book.querySelector('[data-menu-stage]');
    if (tabs.length === 0 || pages.length === 0 || !stage) {
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const pageById = new Map(pages.map((page) => [page.id, page]));
    const tabById = new Map(tabs.map((tab) => [tab.dataset.menuTab, tab]));
    const defaultTabId = pageById.has(book.dataset.activeTab) ? book.dataset.activeTab : tabs[0].dataset.menuTab;
    let activeId = pageById.has(window.location.hash.slice(1))
        ? window.location.hash.slice(1)
        : defaultTabId;
    let isAnimating = false;
    let pendingHistoryId = null;
    let animationTimer;
    let revealObserver;

    // Mantiene las páginas apiladas hasta inicializar pestañas y eventos.
    const setTabState = (id) => {
        tabs.forEach((tab) => {
            const isActive = tab.dataset.menuTab === id;
            tab.setAttribute('aria-selected', String(isActive));
            tab.tabIndex = isActive ? 0 : -1;
        });
    };

    const setPageState = (id) => {
        pages.forEach((page) => {
            const isActive = page.id === id;
            page.classList.toggle('is-active', isActive);
        });
        activeId = id;
        setTabState(id);
    };

    if (prefersReducedMotion) {
        book.classList.add('prefers-reduced-motion');
    }

    // Observa filas de la página activa; las ya reveladas permanecen visibles.
    if (!prefersReducedMotion && 'IntersectionObserver' in window) {
        revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
    }

    const observePage = (page) => {
        page.querySelectorAll('[data-menu-reveal]:not(.is-visible)').forEach((target) => {
            if (revealObserver) {
                revealObserver.observe(target);
            } else {
                target.classList.add('is-visible');
            }
        });
    };

    const scrollToMenu = () => {
        book.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'start' });
    };

    const activatePage = (id, { updateHash = true, shouldScroll = true } = {}) => {
        const nextPage = pageById.get(id);
        const nextTab = tabById.get(id);
        if (!nextPage || !nextTab || isAnimating) {
            return;
        }

        if (id === activeId) {
            if (shouldScroll) {
                scrollToMenu();
            }
            return;
        }

        const currentPage = pageById.get(activeId);
        const currentIndex = tabs.findIndex((tab) => tab.dataset.menuTab === activeId);
        const nextIndex = tabs.findIndex((tab) => tab.dataset.menuTab === id);
        const direction = nextIndex > currentIndex ? 'forward' : 'backward';

        isAnimating = true;
        if (updateHash) {
            history.pushState({ menuTab: id }, '', `#${id}`);
        }
        if (shouldScroll) {
            scrollToMenu();
        }

        stage.style.height = `${currentPage.scrollHeight}px`;
        currentPage.classList.add('is-turning-out', `is-${direction}`);
        nextPage.classList.add('is-turning-in', `is-${direction}`);
        setTabState(id);
        book.classList.add('is-transitioning');

        // Pinta la posición inicial antes de iniciar el giro de página.
        nextPage.getBoundingClientRect();
        requestAnimationFrame(() => {
            currentPage.classList.add('is-in-motion');
            nextPage.classList.add('is-in-motion');
            stage.style.height = `${nextPage.scrollHeight}px`;
        });

        const finishTurn = () => {
            if (!isAnimating) {
                return;
            }

            window.clearTimeout(animationTimer);
            currentPage.classList.remove('is-active', 'is-turning-out', 'is-forward', 'is-backward', 'is-in-motion');
            nextPage.classList.remove('is-turning-in', 'is-forward', 'is-backward', 'is-in-motion');
            nextPage.classList.add('is-active');
            activeId = id;
            book.classList.remove('is-transitioning');
            stage.style.height = `${nextPage.scrollHeight}px`;
            isAnimating = false;
            observePage(nextPage);

            const nextHistoryId = pendingHistoryId;
            pendingHistoryId = null;
            if (nextHistoryId && nextHistoryId !== activeId) {
                activatePage(nextHistoryId, { updateHash: false });
            }
        };

        animationTimer = window.setTimeout(finishTurn, prefersReducedMotion ? 240 : 880);
    };

    setPageState(activeId);
    tabs.forEach((tab) => {
        tab.addEventListener('click', (event) => {
            event.preventDefault();
            activatePage(tab.dataset.menuTab);
        });

        tab.addEventListener('keydown', (event) => {
            if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') {
                return;
            }

            event.preventDefault();
            if (isAnimating) {
                return;
            }

            const currentIndex = tabs.indexOf(tab);
            const direction = event.key === 'ArrowRight' ? 1 : -1;
            const nextTab = tabs[(currentIndex + direction + tabs.length) % tabs.length];
            nextTab.focus();
            activatePage(nextTab.dataset.menuTab);
        });
    });

    window.addEventListener('popstate', () => {
        const id = window.location.hash.slice(1);
        const nextId = pageById.has(id) ? id : defaultTabId;

        if (isAnimating) {
            pendingHistoryId = nextId;

            return;
        }

        activatePage(nextId, { updateHash: false });
    });

    book.classList.add('is-enhanced');
    stage.style.height = `${pageById.get(activeId).scrollHeight}px`;
    if (revealObserver) {
        book.classList.add('has-scroll-reveal');
    }
    observePage(pageById.get(activeId));
})();
</script>
@endsection
