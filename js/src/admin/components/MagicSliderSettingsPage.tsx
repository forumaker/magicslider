import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';

type Slide = { image: string; link?: string; newTab?: boolean };

export default class MagicSliderSettingsPage extends ExtensionPage {
  slides: Slide[] = [];

  oninit(vnode: any) {
    super.oninit(vnode);
    const raw = this.setting('forumaker-magicslider.slides')() || '[]';
    try { this.slides = JSON.parse(raw); } catch { this.slides = []; }
  }

  className() { return 'MagicSliderSettingsPage'; }

  oncreate(vnode: any) {
    const list = vnode.dom.querySelector('.MagicSlides-list') as HTMLElement | null;
    if (!list) return;

    let dragEl: HTMLElement | null = null;

    list.addEventListener('dragstart', (e: any) => {
      const t = (e.target as HTMLElement).closest('.MagicSlides-item') as HTMLElement | null;
      if (!t) return;
      dragEl = t;
      e.dataTransfer.effectAllowed = 'move';
      t.classList.add('is-dragging');
    });

    list.addEventListener('dragend', () => {
      if (dragEl) dragEl.classList.remove('is-dragging');
      dragEl = null;
    });

    list.addEventListener('dragover', (e) => {
      e.preventDefault();
      const over = (e.target as HTMLElement).closest('.MagicSlides-item') as HTMLElement | null;
      if (!dragEl || !over || over === dragEl) return;
      const rect = over.getBoundingClientRect();
      const after = (e as MouseEvent).clientY - rect.top > rect.height / 2;
      over.parentElement?.insertBefore(dragEl, after ? over.nextSibling : over);
    });

    list.addEventListener('drop', () => {
      const newOrder: Slide[] = [];
      const items = list.querySelectorAll('.MagicSlides-item');
      items.forEach((el: any) => {
        const i = Number(el.dataset.index);
        const s = this.slides[i];
        if (s) newOrder.push(s);
      });
      this.slides = newOrder;
      this.syncSlides();
    });
  }

  content() {
    return (
      <div className="MagicSliderSettingsPage">
        <div className="MagicSliderSettingsPage-content">

          <section className="MagicSlider-SettingsSection">
            <h3>
              <i className="fas fa-desktop" />
              {app.translator.trans('capy-magic-slider.admin.settings.section_desktop')}
            </h3>
            <div className="MagicSlider-SettingsSection-content">
              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.height_desktop',
                label: app.translator.trans('capy-magic-slider.admin.settings.height'),
                help: app.translator.trans('capy-magic-slider.admin.settings.height_help'),
                min: 100,
              })}
              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.padding_desktop',
                label: app.translator.trans('capy-magic-slider.admin.settings.padding'),
                help: app.translator.trans('capy-magic-slider.admin.settings.padding_help'),
                min: 0,
              })}
              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.radius_desktop',
                label: app.translator.trans('capy-magic-slider.admin.settings.radius'),
                help: app.translator.trans('capy-magic-slider.admin.settings.radius_help'),
                min: 0,
              })}
            </div>
          </section>

          <section className="MagicSlider-SettingsSection">
            <h3>
              <i className="fas fa-mobile-alt" />
              {app.translator.trans('capy-magic-slider.admin.settings.section_mobile')}
            </h3>
            <div className="MagicSlider-SettingsSection-content">
              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.height_mobile',
                label: app.translator.trans('capy-magic-slider.admin.settings.height'),
                help: app.translator.trans('capy-magic-slider.admin.settings.height_help'),
                min: 100,
              })}
              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.padding_mobile',
                label: app.translator.trans('capy-magic-slider.admin.settings.padding'),
                help: app.translator.trans('capy-magic-slider.admin.settings.padding_help'),
                min: 0,
              })}
              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.radius_mobile',
                label: app.translator.trans('capy-magic-slider.admin.settings.radius'),
                help: app.translator.trans('capy-magic-slider.admin.settings.radius_help'),
                min: 0,
              })}
            </div>
          </section>

          <section className="MagicSlider-SettingsSection">
            <h3>
              <i className="fas fa-cog" />
              {app.translator.trans('capy-magic-slider.admin.settings.section_behavior')}
            </h3>
            <div className="MagicSlider-SettingsSection-content">
              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.autoplay',
                label: app.translator.trans('capy-magic-slider.admin.settings.autoplay'),
                help: app.translator.trans('capy-magic-slider.admin.settings.autoplay_help'),
                min: 0,
              })}
              {this.buildSettingComponent({
                type: 'boolean',
                setting: 'forumaker-magicslider.hide_on_tag_pages',
                label: app.translator.trans('capy-magic-slider.admin.settings.hide_on_tag_pages'),
                help: app.translator.trans('capy-magic-slider.admin.settings.hide_on_tag_pages_help'),
              })}
              {this.buildSettingComponent({
                type: 'boolean',
                setting: 'forumaker-magicslider.fit_to_layout',
                label: app.translator.trans('capy-magic-slider.admin.settings.fit_to_layout'),
                help: app.translator.trans('capy-magic-slider.admin.settings.fit_to_layout_help'),
              })}
            </div>
          </section>

          <section className="MagicSlider-SettingsSection">
            <h3>
              <i className="fas fa-sliders-h" />
              {app.translator.trans('capy-magic-slider.admin.settings.section_slides')}
            </h3>
            <div className="MagicSlider-SettingsSection-content">
              <div className="Form-group">
                <div className="MagicSlides-toolbar">
                  <Button className="Button Button--primary" onclick={() => this.add()}>
                    <i className="fas fa-plus" /> {app.translator.trans('capy-magic-slider.admin.settings.add_slide')}
                  </Button>
                </div>

                <div className="MagicSlides-list">
                  {this.slides.map((s, i) => (
                    <div key={i} className="MagicSlides-item" data-index={i} draggable="true">
                      <span className="MagicSlides-handle" title={app.translator.trans('capy-magic-slider.admin.settings.drag')}>
                        <i className="fas fa-grip-vertical" />
                      </span>

                      <input
                        className="FormControl"
                        type="text"
                        placeholder={app.translator.trans('capy-magic-slider.admin.settings.image_placeholder')}
                        value={s.image}
                        oninput={(e: any) => { s.image = e.target.value; this.syncSlides(); }}
                      />

                      <input
                        className="FormControl MagicSlides-link"
                        type="text"
                        placeholder={app.translator.trans('capy-magic-slider.admin.settings.link_placeholder')}
                        value={s.link || ''}
                        oninput={(e: any) => { s.link = e.target.value; this.syncSlides(); }}
                      />

                      <Button
                        className={'Button MagicSlides-toggle' + (s.newTab ? ' is-active' : '')}
                        onclick={() => { s.newTab = !s.newTab; this.syncSlides(); }}
                      >
                        <i className="fas fa-external-link-alt" />
                        <span>{app.translator.trans('capy-magic-slider.admin.settings.new_tab')}</span>
                      </Button>

                      <div className="MagicSlides-actions">
                        <Button className="Button Button--danger" onclick={() => this.remove(i)}>
                          <i className="fas fa-trash" />
                          <span>{app.translator.trans('capy-magic-slider.admin.settings.delete')}</span>
                        </Button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          </section>

          {this.submitButton()}
        </div>
      </div>
    );
  }

  add() {
    this.slides.push({ image: '', link: '', newTab: false });
    this.syncSlides();
    setTimeout(() => m.redraw(), 0);
  }

  remove(i: number) {
    this.slides.splice(i, 1);
    this.syncSlides();
  }

  syncSlides() {
    this.setting('forumaker-magicslider.slides')(JSON.stringify(this.slides));
    m.redraw();
  }
}