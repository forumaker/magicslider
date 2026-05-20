import app from 'flarum/forum/app';
import { override } from 'flarum/common/extend';
import IndexPage from 'flarum/forum/components/IndexPage';
import MagicSlider from './components/MagicSlider';

type Slide = { image: string; link?: string; newTab?: boolean | string };

function isTrue(v: unknown): boolean {
  return v === true || v === 1 || v === '1' || v === 'true';
}

function getBool(key: string): boolean {
  const value = app.forum.attribute(key);
  if (value === undefined || value === null) return false;
  return isTrue(value);
}

function isMobileViewport(): boolean {
  return (
    typeof window !== 'undefined' &&
    !!window.matchMedia &&
    window.matchMedia('(max-width: 768px)').matches
  );
}

function isSingleTagPage(): boolean {
  const p1 = (typeof location !== 'undefined' && location.pathname) || '';
  if (p1 && /(?:^|\/)t(?:\/|$)/.test(p1)) return true;
  if (p1 && /(?:^|\/)tags(?:\/|$)/.test(p1)) return false;

  const p2 = (window as any)?.m?.route?.get?.() || '';
  if (p2 && /(?:^|\/)t(?:\/|$)/.test(p2)) return true;
  if (p2 && /(?:^|\/)tags(?:\/|$)/.test(p2)) return false;

  return false;
}

app.initializers.add('forumaker-magicslider', () => {
  override(IndexPage.prototype, 'hero', function (original: any) {
    if (getBool('forumaker-magicslider.hide_on_tag_pages') && isSingleTagPage()) return original();

    const disableMobile = getBool('forumaker-magicslider.disable_mobile');
    const disableDesktop = getBool('forumaker-magicslider.disable_desktop');
    const mobile = isMobileViewport();

    if ((mobile && disableMobile) || (!mobile && disableDesktop)) return original();

    const rawSlides = app.forum.attribute<string>('forumaker-magicslider.slides') || '[]';
    let slides: Slide[] = [];
    try {
      slides = JSON.parse(rawSlides) as Slide[];
    } catch {
      slides = [];
    }

    const normSlides = slides
      .filter((s) => s && s.image)
      .map((s) => ({ image: s.image || '', link: s.link || '', newTab: s.newTab === true || s.newTab === 'true' }));

    if (!normSlides.length) return original();

    const heightDesktop = Number(app.forum.attribute('forumaker-magicslider.height_desktop') || 260);
    const heightMobile = Number(app.forum.attribute('forumaker-magicslider.height_mobile') || 200);
    const paddingDesktop = Number(app.forum.attribute('forumaker-magicslider.padding_desktop') || 0);
    const paddingMobile = Number(app.forum.attribute('forumaker-magicslider.padding_mobile') || 0);
    const radiusDesktop = Number(app.forum.attribute('forumaker-magicslider.radius_desktop') || 0);
    const radiusMobile = Number(app.forum.attribute('forumaker-magicslider.radius_mobile') || 0);

    const autoplaySeconds = Number(app.forum.attribute('forumaker-magicslider.autoplay') || 0);
    const autoplayMs = autoplaySeconds > 0 ? autoplaySeconds * 1000 : 0;

    const fitToLayout = getBool('forumaker-magicslider.fit_to_layout');

    return (
      <MagicSlider
        slides={normSlides}
        heightDesktop={heightDesktop}
        heightMobile={heightMobile}
        paddingDesktop={paddingDesktop}
        paddingMobile={paddingMobile}
        radiusDesktop={radiusDesktop}
        radiusMobile={radiusMobile}
        autoplayMs={autoplayMs}
        fitToLayout={fitToLayout}
      />
    );
  });
});