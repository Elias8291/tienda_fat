import '../../css/store.css';
import { Head } from '@inertiajs/react';
import { useEffect, useMemo, useRef, useState } from 'react';
import { Bag, ChevronRight, Close, Dress, Hanger, Heart, Shirt, Sparkle, Trash, WhatsApp } from '../components/Icons';
import { discount, money } from '../lib/money';

const CART_KEY = 'fati-cart';
const WHATSAPP = '529511879940';
const HEARTS = [[8, 12], [78, 9], [16, 70], [86, 62], [60, 84], [28, 30], [70, 36]];
const BRAND = 'Fati Nails';
const WEAR = [
    { Icon: Hanger, x: 7, y: 16, delay: 0.25 },
    { Icon: Dress, x: 84, y: 30, delay: 0.45 },
    { Icon: Shirt, x: 80, y: 72, delay: 0.65 },
    { Icon: Bag, x: 9, y: 52, delay: 0.85 },
];
const photo = (id, w = 1000) => `https://images.unsplash.com/photo-${id}?auto=format&fit=crop&q=80&w=${w}`;

const go = (id) => document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
const cover = (product) => product.images[0]?.url;

function contactLabel(number) {
    const digits = String(number).replace(/\D/g, '');
    const local = digits.startsWith('52') && digits.length === 12 ? digits.slice(2) : digits;
    return local.length === 10 ? `${local.slice(0, 3)} ${local.slice(3, 6)} ${local.slice(6)}` : local;
}

function Photo({ src, alt, eager = false }) {
    const [shown, setShown] = useState('');
    const ready = shown === src;

    return (
        <>
            {!ready && <span className="bone" aria-hidden="true" />}
            <img
                src={src}
                alt={alt}
                className={ready ? 'on' : ''}
                loading={eager ? 'eager' : 'lazy'}
                decoding="async"
                fetchPriority={eager ? 'high' : 'low'}
                onLoad={(e) => {
                    if (e.currentTarget.naturalWidth > 0) setShown(src);
                }}
                ref={(node) => {
                    if (node?.complete && node.naturalWidth > 0) setShown(src);
                }}
            />
        </>
    );
}

function CardPhoto({ product, onOpen, eager = false }) {
    const images = product.images;
    const [index, setIndex] = useState(0);
    const startX = useRef(0);
    const swiped = useRef(false);
    const off = discount(product);
    const step = (dir) => setIndex((n) => (n + dir + images.length) % images.length);

    useEffect(() => {
        images.forEach((image) => {
            const preload = new Image();
            preload.src = image.url;
        });
    }, [images]);

    return (
        <div
            className="pic"
            onTouchStart={(e) => {
                startX.current = e.changedTouches[0].clientX;
                swiped.current = false;
            }}
            onTouchEnd={(e) => {
                if (images.length < 2) return;
                const dx = e.changedTouches[0].clientX - startX.current;
                if (Math.abs(dx) < 36) return;
                swiped.current = true;
                step(dx < 0 ? 1 : -1);
            }}
        >
            <button
                type="button"
                className="pic-hit"
                onClick={() => {
                    if (swiped.current) {
                        swiped.current = false;
                        return;
                    }
                    onOpen();
                }}
                aria-label={`Ver ${product.name}`}
            >
                {images[index] && <Photo src={images[index].url} alt={product.name} eager={eager && index === 0} />}
            </button>
            {off > 0 && <span className="tag">−{off}%</span>}
        </div>
    );
}

function Price({ product }) {
    const off = discount(product);
    return (
        <div className="price">
            {off > 0 && <s>{money(product.old_price)}</s>}
            <b>{money(product.price)}</b>
        </div>
    );
}

export default function Store({ products, whatsapp }) {
    const [cart, setCart] = useState(() => {
        try {
            return JSON.parse(localStorage.getItem(CART_KEY)) ?? [];
        } catch {
            return [];
        }
    });
    const [cartOpen, setCartOpen] = useState(false);
    const [viewing, setViewing] = useState(null);
    const [shot, setShot] = useState(0);
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [error, setError] = useState('');
    const [confirming, setConfirming] = useState(false);
    const shotX = useRef(0);
    const drawerRef = useRef(null);
    const scrollLock = useRef(null);
    const line = String(whatsapp || '').replace(/\D/g, '') || WHATSAPP;

    const items = useMemo(() => cart.map((id) => products.find((p) => p.id === id)).filter(Boolean), [cart, products]);
    const total = items.reduce((sum, p) => sum + p.price, 0);
    const inCart = (id) => items.some((p) => p.id === id);

    useEffect(() => {
        localStorage.setItem(CART_KEY, JSON.stringify(items.map((p) => p.id)));
    }, [items]);

    useEffect(() => {
        const open = cartOpen || viewing || confirming;
        if (open) {
            if (scrollLock.current === null) scrollLock.current = window.scrollY;
            document.body.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.width = '100%';
            document.body.style.top = `-${scrollLock.current}px`;
            return;
        }
        const y = scrollLock.current ?? 0;
        scrollLock.current = null;
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';
        document.body.style.top = '';
        window.scrollTo(0, y);
    }, [cartOpen, viewing, confirming]);

    useEffect(() => {
        if (!cartOpen) return undefined;
        const drawer = drawerRef.current;
        const viewport = window.visualViewport;
        if (!drawer || !viewport) return undefined;
        const fit = () => {
            drawer.style.height = `${viewport.height}px`;
            drawer.style.top = `${viewport.offsetTop}px`;
        };
        fit();
        viewport.addEventListener('resize', fit);
        viewport.addEventListener('scroll', fit);
        return () => {
            viewport.removeEventListener('resize', fit);
            viewport.removeEventListener('scroll', fit);
            drawer.style.height = '';
            drawer.style.top = '';
        };
    }, [cartOpen]);

    useEffect(() => {
        const onKey = (e) => {
            if (e.key === 'Escape') {
                setConfirming(false);
                setViewing(null);
                setCartOpen(false);
            }
        };
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, []);

    const toggleItem = (id) => {
        if (inCart(id)) {
            setCart((c) => c.filter((i) => i !== id));
            return;
        }
        setCart((c) => [...c, id]);
        setCartOpen(true);
    };

    const openProduct = (product) => {
        setShot(0);
        setViewing(product);
    };

    const sendOrder = () => {
        if (!items.length) return;
        if (!name.trim()) {
            setError('Escribe tu nombre para continuar.');
            return;
        }
        if (phone.replace(/\D/g, '').length < 10) {
            setError('Escribe tu número de teléfono (10 dígitos) para continuar.');
            return;
        }
        setError('');
        setConfirming(true);
    };

    const openWhatsApp = () => {
        setConfirming(false);
        const lines = items.map((p) => `• ${p.name} — ${money(p.price)}`);
        const msg =
            `Hola, soy ${name.trim()}. Me gustaría pedir:\n\n${lines.join('\n')}\n\nTotal: ${money(total)}` +
            `\n\nMi teléfono: ${phone.trim()}` +
            '\n\n¿Me confirmas disponibilidad? ¡Gracias!';
        window.open(`https://wa.me/${line}?text=${encodeURIComponent(msg)}`, '_blank');
    };

    return (
        <>
            <Head title="Tienda" />
            <div className="page">
                <header className="s-header">
                    <div className="bar">
                        <div className="links l1">
                            <button onClick={() => go('catalogo')}>Catálogo</button>
                        </div>
                        <button className="logo" onClick={() => window.scrollTo({ top: 0, behavior: 'smooth' })}>Fati Nails</button>
                        <div className="right">
                            <div className="links l2">
                                <button onClick={() => go('marca')}>La marca</button>
                                <button onClick={() => go('contacto')}>Contacto</button>
                            </div>
                            <div className="icons">
                                <button className="ico" onClick={() => setCartOpen(true)} aria-label="Abrir pedido">
                                    <Bag />
                                    <span className="badge">{items.length}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </header>

                <section className="hero">
                    <div className="seal" aria-hidden="true"><Shirt /></div>
                    <div className="panel">
                        <div className="hearts" aria-hidden="true">
                            {HEARTS.map(([x, y], i) => (
                                <span key={`${x}-${y}`} style={{ left: `${x}%`, top: `${y}%`, '--i': i }}><Heart /></span>
                            ))}
                        </div>
                        <div className="wear" aria-hidden="true">
                            {WEAR.map(({ Icon, x, y, delay }) => (
                                <span key={`${x}-${y}`} style={{ left: `${x}%`, top: `${y}%`, '--d': `${delay}s` }}><Icon /></span>
                            ))}
                        </div>
                        <h1 aria-label={BRAND}>
                            {[...BRAND].map((char, i) => (
                                <span key={i} className="l" style={{ '--i': i }} aria-hidden="true">
                                    {char === ' ' ? '\u00A0' : char}
                                </span>
                            ))}
                        </h1>
                        <p>Ropa linda y cómoda para tu día a día, con pedidos por WhatsApp.</p>
                        <button className="chip" onClick={() => go('catalogo')}>Catálogo</button>
                        <span className="sp" aria-hidden="true"><Sparkle /></span>
                    </div>
                    <div className="photo">
                        <img src={photo('1485230895905-ec40ba36b9bc')} alt="Modelo con la nueva colección" />
                    </div>
                </section>

                <section id="catalogo">
                    <div className="center">
                        <h2 className="title">Catálogo <span className="sp" aria-hidden="true"><Sparkle /></span></h2>
                    </div>
                    <div className="grid">
                        {products.length === 0 && <p className="none">Pronto tendremos nuevas piezas.</p>}
                        {products.map((p, i) => (
                            <article className="card" key={p.id}>
                                <CardPhoto product={p} eager={i < 4} onOpen={() => openProduct(p)} />
                                <h3 onClick={() => openProduct(p)}>{p.name}</h3>
                                <Price product={p} />
                                <button className={`add ${inCart(p.id) ? 'in' : ''}`} onClick={() => toggleItem(p.id)}>
                                    {inCart(p.id) ? 'En el pedido' : 'Añadir al pedido'}
                                </button>
                            </article>
                        ))}
                    </div>
                </section>

                <section className="about" id="marca">
                    <div className="stack" aria-hidden="true">
                        <img src={photo('1434389677669-e08b4cac3105', 500)} alt="" />
                        <img src={photo('1594633312681-425c7b97ccd1', 500)} alt="" />
                    </div>
                    <div className="text">
                        <h2 className="title">Sobre la marca</h2>
                        <p>Somos una tienda pequeña que elige cada pieza con cariño: prendas cómodas, femeninas y fáciles de combinar. Escríbenos y te ayudamos a armar tu look.</p>
                        <button className="s-btn" onClick={() => go('catalogo')}>Catálogo</button>
                    </div>
                    <div className="frame">
                        <img src={photo('1534528741775-53994a69daeb', 600)} alt="Fati Nails" />
                        <span className="sp" aria-hidden="true"><Heart /></span>
                    </div>
                </section>

                <footer className="s-footer" id="contacto">
                    <span className="logo">Fati Nails</span>
                    <a className="contact" href={`https://wa.me/${line}`} target="_blank" rel="noreferrer">
                        <WhatsApp /> {contactLabel(line)}
                    </a>
                    Pedidos por WhatsApp · © {new Date().getFullYear()} Fati Nails
                </footer>
            </div>

            <div className={`veil ${cartOpen ? 'open' : ''}`} onClick={() => setCartOpen(false)} />

            <aside ref={drawerRef} className={`drawer ${cartOpen ? 'open' : ''}`} aria-label="Tu pedido">
                <div className="dhead">
                    <h2>Tu pedido</h2>
                    <button className="x" onClick={() => setCartOpen(false)} aria-label="Cerrar"><Close /></button>
                </div>
                <div className="items">
                    {items.length === 0 ? (
                        <div className="empty"><p>Tu pedido está vacío.</p></div>
                    ) : (
                        items.map((p) => (
                            <div className="line" key={p.id}>
                                <img src={cover(p)} alt={p.name} />
                                <div>
                                    <h4>{p.name}</h4>
                                    <div className="pr">{money(p.price)}</div>
                                </div>
                                <button className="del" onClick={() => toggleItem(p.id)} aria-label="Quitar"><Trash /></button>
                            </div>
                        ))
                    )}
                </div>
                <div className="dfoot">
                    <div className="sub"><span>Total</span><span>{money(total)}</span></div>
                    <input className="s-field" value={name} onChange={(e) => setName(e.target.value)} placeholder="Tu nombre *" autoComplete="name" required />
                    <input className="s-field" value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="Tu teléfono *" type="tel" inputMode="tel" autoComplete="tel" required />
                    <div className="hint">Así podremos contactarte y confirmar tu pedido.</div>
                    {error && <div className="s-err">{error}</div>}
                    <button className="cta" onClick={sendOrder} disabled={!items.length}>
                        <WhatsApp /> Enviar pedido por WhatsApp
                    </button>
                </div>
            </aside>

            {viewing && (
                <div className="modal" onClick={(e) => e.target === e.currentTarget && setViewing(null)}>
                    <div className="mbox">
                        <button className="x" onClick={() => setViewing(null)} aria-label="Cerrar"><Close /></button>
                        <div
                            className="mpics"
                            onTouchStart={(e) => { shotX.current = e.changedTouches[0].clientX; }}
                            onTouchEnd={(e) => {
                                if (viewing.images.length < 2) return;
                                const dx = e.changedTouches[0].clientX - shotX.current;
                                if (Math.abs(dx) < 36) return;
                                setShot((n) => (n + (dx < 0 ? 1 : -1) + viewing.images.length) % viewing.images.length);
                            }}
                        >
                            {viewing.images[shot] && <Photo src={viewing.images[shot].url} alt={viewing.name} eager />}
                            {discount(viewing) > 0 && <span className="tag">−{discount(viewing)}%</span>}
                            {viewing.images.length > 1 && (
                                <button
                                    type="button"
                                    className="next"
                                    onClick={() => setShot((n) => (n + 1) % viewing.images.length)}
                                    aria-label="Foto siguiente"
                                >
                                    <ChevronRight />
                                </button>
                            )}
                            {viewing.images.length > 1 && (
                                <div className="thumbs">
                                    {viewing.images.map((img, i) => (
                                        <button key={img.id} className={i === shot ? 'on' : ''} onClick={() => setShot(i)} aria-label={`Foto ${i + 1}`} />
                                    ))}
                                </div>
                            )}
                        </div>
                        <div className="mbody">
                            <h3>{viewing.name}</h3>
                            <Price product={viewing} />
                            {viewing.description && <p>{viewing.description}</p>}
                            <button
                                className="add"
                                onClick={() => {
                                    setViewing(null);
                                    inCart(viewing.id) ? setCartOpen(true) : toggleItem(viewing.id);
                                }}
                            >
                                {inCart(viewing.id) ? 'Ver mi pedido' : 'Añadir al pedido'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {confirming && (
                <div className="modal" onClick={(e) => e.target === e.currentTarget && setConfirming(false)}>
                    <div className="cbox" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
                        <button className="x" onClick={() => setConfirming(false)} aria-label="Cerrar"><Close /></button>
                        <span className="cicon" aria-hidden="true"><WhatsApp /></span>
                        <h3 id="confirm-title">Envía tu pedido por WhatsApp</h3>
                        <p>
                            Se abrirá WhatsApp con tu pedido ya escrito, <b>solo presiona enviar</b>. Ahí lo revisamos
                            y nos pondremos en contacto contigo para coordinar la entrega y el pago.
                        </p>
                        <div className="sub"><span>Total</span><span>{money(total)}</span></div>
                        <button className="cta" onClick={openWhatsApp}>
                            <WhatsApp /> Ir a WhatsApp
                        </button>
                        <button className="cback" onClick={() => setConfirming(false)}>Volver al pedido</button>
                    </div>
                </div>
            )}
        </>
    );
}
