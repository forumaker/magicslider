import Component, { ComponentAttrs } from 'flarum/common/Component';

type Slide = { image: string; link?: string; newTab?: boolean };

interface MagicSliderAttrs extends ComponentAttrs {
  slides: Slide[];
  heightDesktop: number;
  heightMobile: number;
  paddingDesktop: number;
  paddingMobile: number;
  radiusDesktop: number;
  radiusMobile: number;
  autoplayMs: number;
  fitToLayout?: boolean;
}

export default class MagicSlider extends Component<MagicSliderAttrs> {
  private index = 0;
  private interval?: number;
  private touchStartX: number | null = null;
  private isMobile = typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(max-width: 768px)').matches;
  private mq?: MediaQueryList;

  oncreate(vnode: any) {
    const ms = Number(vnode.attrs.autoplayMs || 0);
    if (vnode.attrs.slides.length > 1 && ms > 0) {
      this.interval = window.setInterval(() => {
        this.next(vnode.attrs.slides.length);
        m.redraw();
      }, ms);
    }

    if (window.matchMedia) {
      this.mq = window.matchMedia('(max-width: 768px)');
      const handler = () => { this.isMobile = !!this.mq?.matches; m.redraw(); };
      this.mq.addEventListener ? this.mq.addEventListener('change', handler) : this.mq.addListener(handler);
    }
  }

  onremove() {
    if (this.interval) window.clearInterval(this.interval);
    if (this.mq) {
      const handler = () => { /* no-op */ };
      this.mq.removeEventListener ? this.mq.removeEventListener('change', handler) : this.mq.removeListener(handler);
    }
  }

  private next(len: number) { this.index = (this.index + 1) % len; }
  private prev(len: number) { this.index = (this.index - 1 + len) % len; }

  private onPointerDown(e: PointerEvent) { this.touchStartX = e.clientX ?? 0; }
  private onPointerUp(e: PointerEvent, len: number) {
    if (this.touchStartX === null) return;
    const dx = (e.clientX ?? 0) - this.touchStartX;
    this.touchStartX = null;
    if (Math.abs(dx) > 40) { dx < 0 ? this.next(len) : this.prev(len); m.redraw(); }
  }

  private viewInner(vnode: any) {
    const {
      slides, heightDesktop, heightMobile,
      paddingDesktop, paddingMobile, radiusDesktop, radiusMobile
    } = vnode.attrs;

    if (!slides.length) return null;

    const offsetPct = -(this.index * 100);
    const pad = this.isMobile ? paddingMobile : paddingDesktop;
    const rad = this.isMobile ? radiusMobile : radiusDesktop;

    return (
      <div className="MagicSlider-wrap" style={{ padding: `${pad}px` }}>
        <div
          className="MagicSlider"
          style={{
            borderRadius: `${rad}px`,
            ['--ms-h-desktop' as any]: `${heightDesktop}px`,
            ['--ms-h-mobile' as any]: `${heightMobile}px`,
          }}
          onpointerdown={(e: any) => this.onPointerDown(e)}
          onpointerup={(e: any) => this.onPointerUp(e, slides.length)}
        >
          <div className="MagicSlider-track" style={{ transform: `translateX(${offsetPct}%)` }}>
            {slides.map((s: Slide, i: number) => (
              <a
                key={i}
                className="MagicSlide"
                href={s.link || '#'}
                target={s.link ? (s.newTab ? '_blank' : '_self') : undefined}
                rel={s.link && s.newTab ? 'noopener' : undefined}
                style={{ backgroundImage: `url('${s.image}')` }}
                onclick={(e: MouseEvent) => { if (!s.link) e.preventDefault(); }}
              />
            ))}
          </div>

          {slides.length > 1 && (
            <>
              <button className="MagicSlider-btn prev" aria-label="Previous" onclick={() => { this.prev(slides.length); }}>
                <i className="fas fa-chevron-left"></i>
              </button>
              <button className="MagicSlider-btn next" aria-label="Next" onclick={() => { this.next(slides.length); }}>
                <i className="fas fa-chevron-right"></i>
              </button>
              <div className="MagicSlider-dots">
                {slides.map((_, i) => (
                  <button
                    key={i}
                    className={'dot' + (this.index === i ? ' is-active' : '')}
                    aria-label={`Slide ${i + 1}`}
                    onclick={() => { this.index = i; }}
                  />
                ))}
              </div>
            </>
          )}
        </div>
      </div>
    );
  }

  view(vnode: any) {
    const { fitToLayout } = vnode.attrs;
    if (fitToLayout) {
      return (
        <div className="MagicSlider-outer">
          <div className="container">
            <div className="sideNavOffset">
              {this.viewInner(vnode)}
            </div>
          </div>
        </div>
      );
    }
    return this.viewInner(vnode);
  }
}