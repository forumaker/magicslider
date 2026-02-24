import app from 'flarum/forum/app';
import { override } from 'flarum/common/extend';

import IndexPage from 'flarum/forum/components/IndexPage';
import WelcomeHero from 'flarum/forum/components/WelcomeHero';

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
  const cur = (app as any).current;

  if (cur?.routeName?.startsWith('tags.')) return true;

  const p = location?.pathname || '';
  if (/^\/t\/.+/.test(p)) return true;

  return false;
}

function buildSlider() {
  if (getBool('forumaker-magicslider.hide_on_tag_pages') && isSingleTagPage()) {
    return null;
  }

  const disableMobile = getBool('forumaker-magicslider.disable_mobile');
  const disableDesktop = getBool('forumaker-magicslider.disable_desktop');
  const mobile = isMobileViewport();

  if ((mobile && disableMobile) || (!mobile && disableDesktop)) {
    return null;
  }

  const raw = app.forum.attribute<string>('forumaker-magicslider.slides') || '[]';

  let slides: Slide[] = [];
  try {
    slides = JSON.parse(raw) as Slide[];
  } catch {
    slides = [];
  }

  const norm = slides
    .filter((s) => s && s.image)
    .map((s) => ({
      image: s.image || '',
      link: s.link || '',
      newTab: s.newTab === true || s.newTab === 'true',
    }));

  if (!norm.length) return null;

  const heightDesktop = Number(app.forum.attribute('forumaker-magicslider.height_desktop') || 260);
  const heightMobile = Number(app.forum.attribute('forumaker-magicslider.height_mobile') || 200);
  const paddingDesktop = Number(app.forum.attribute('forumaker-magicslider.padding_desktop') || 0);
  const paddingMobile = Number(app.forum.attribute('forumaker-magicslider.padding_mobile') || 0);
  const radiusDesktop = Number(app.forum.attribute('forumaker-magicslider.radius_desktop') || 0);
  const radiusMobile = Number(app.forum.attribute('forumaker-magicslider.radius_mobile') || 0);

  // NOW IN SECONDS:
  const autoplaySeconds = Number(app.forum.attribute('forumaker-magicslider.autoplay') || 0);
  const autoplayMs = autoplaySeconds > 0 ? autoplaySeconds * 1000 : 0;

  const fitToLayout = getBool('forumaker-magicslider.fit_to_layout');

  return (
    <MagicSlider
      slides={norm}
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
}

function replaceHero(original: () => Mithril.Children) {
  const slider = buildSlider();
  return slider ?? original();
}

app.initializers.add('forumaker-magicslider', () => {
  try {
    app.forum.on('refresh', () => m.redraw());
  } catch {}

  override(IndexPage.prototype, 'hero', function (original: any) {
    return replaceHero(original);
  });

  override(WelcomeHero.prototype, 'view', function (original: any) {
    return replaceHero(original);
  });
});