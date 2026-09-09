import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import Switch from 'flarum/common/components/Switch';
import saveSettings from 'flarum/admin/utils/saveSettings';

// `id` is a real, persisted part of a slide's data now (unlike the old
// client-only sequence number this used to carry) — it's the primary key
// RecordSlideClickController/GetSlideClicksController key click counts
// against, so it has to survive edits, reordering, and reloads, not just
// last long enough for one Mithril `key`/drag session. Slides saved before
// this existed get one backfilled on load (see oninit) and immediately
// persisted, so the very first click after an upgrade already has
// somewhere to land.
type Slide = { id: string; image: string; link?: string; newTab?: boolean };

function genId(): string {
  return Math.random().toString(36).slice(2) + Date.now().toString(36);
}

function isTrue(v: unknown): boolean {
  return v === true || v === 1 || v === '1' || v === 'true';
}

export default class MagicSliderSettingsPage extends ExtensionPage {
  slides: Slide[] = [];
  clicks: Record<string, number> = {};
  uploadingId: string | null = null;
  dragIndex: number | null = null;

  oninit(vnode: any) {
    super.oninit(vnode);

    const raw = this.setting('forumaker-magicslider.slides')() || '[]';
    let parsed: Partial<Slide>[] = [];

    try {
      parsed = JSON.parse(raw);
    } catch {
      parsed = [];
    }

    let backfilled = false;
    this.slides = parsed.map((s) => {
      if (s.id) return s as Slide;
      backfilled = true;
      return { ...s, id: genId() } as Slide;
    });

    if (backfilled) {
      // syncSlides() only updates the local Stream — on this settings page,
      // same as every other, nothing actually reaches the server until the
      // admin clicks "Save". A silent id backfill can't wait for that: it's
      // not a change the admin asked for or would think to save, so this
      // calls the same save-settings endpoint the Submit button itself
      // uses, directly, right away — otherwise the ids reset on every page
      // load until someone happens to hit Save for an unrelated reason, and
      // clicks recorded against a backfilled id that got regenerated next
      // load orphan themselves.
      const raw = JSON.stringify(this.slides);
      this.setting('forumaker-magicslider.slides')(raw);
      saveSettings({ 'forumaker-magicslider.slides': raw }).catch(() => {});
    }

    this.loadClicks();
  }

  className() {
    return 'MagicSliderSettingsPage';
  }

  private loadClicks(): void {
    app
      .request<{ data?: Record<string, number> }>({
        method: 'GET',
        url: `${app.forum.attribute('apiUrl')}/forumaker/magicslider/clicks`,
      })
      .then((response) => {
        this.clicks = response?.data ?? {};
        m.redraw();
      })
      .catch(() => {});
  }

  private onDragStart(index: number, e: DragEvent) {
    this.dragIndex = index;
    if (e.dataTransfer) e.dataTransfer.effectAllowed = 'move';
  }

  private onDragOver(index: number, e: DragEvent) {
    e.preventDefault();

    if (this.dragIndex === null || this.dragIndex === index) return;

    const [moved] = this.slides.splice(this.dragIndex, 1);
    this.slides.splice(index, 0, moved);
    this.dragIndex = index;
  }

  private onDragEnd() {
    if (this.dragIndex === null) return;

    this.dragIndex = null;
    this.syncSlides();
  }

  /** Matches exactly what UploadSlideImageController generates — the same check the delete endpoint itself makes server-side. */
  private isOwnUpload(url: string): boolean {
    return /\/assets\/magicslider\/[a-f0-9]{32}\.[a-z0-9]+$/i.test(url);
  }

  /**
   * Best-effort cleanup for a file this extension previously uploaded —
   * called after it's been replaced or its slide removed, so replaced/
   * removed slide images don't just accumulate on disk forever. Never
   * awaited by its caller and never surfaces an error: this runs after
   * something else already succeeded (a new upload landed, a slide was
   * deleted), so there's nothing left for the user to react to either way.
   */
  private deleteUploadedImage(url: string): void {
    if (!this.isOwnUpload(url)) return;

    app
      .request({
        method: 'DELETE',
        url: `${app.forum.attribute('apiUrl')}/forumaker/magicslider/upload`,
        body: { url },
      })
      .catch(() => {});
  }

  async uploadImage(file: File, slide: Slide) {
    this.uploadingId = slide.id;
    m.redraw();

    try {
      const formData = new FormData();
      formData.append('image', file);

      const response = await app.request<{ data?: { url?: string } }>({
        method: 'POST',
        url: `${app.forum.attribute('apiUrl')}/forumaker/magicslider/upload`,
        body: formData,
        serialize: (body: any) => body,
      });

      const url = response?.data?.url;

      if (url) {
        const previousImage = slide.image;
        slide.image = url;
        this.syncSlides();
        if (previousImage) this.deleteUploadedImage(previousImage);
      } else {
        throw new Error('Upload completed but no URL returned');
      }
    } catch {
      app.alerts.show({ type: 'error' }, app.translator.trans('forumaker-magicslider.admin.settings.upload_error'));
    } finally {
      this.uploadingId = null;
      m.redraw();
    }
  }

  content() {
    const disableDesktop = isTrue(this.setting('forumaker-magicslider.disable_desktop')());
    const disableMobile = isTrue(this.setting('forumaker-magicslider.disable_mobile')());

    return (
      <div className="MagicSliderSettingsPage">
        <div className="MagicSliderSettingsPage-content">
          <section className="MagicSlider-SettingsSection">
            <h3>
              <i className="fas fa-desktop" />
              {app.translator.trans('forumaker-magicslider.admin.settings.section_desktop')}
            </h3>

            <div className="MagicSlider-SettingsSection-content">
              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.height_desktop',
                label: app.translator.trans('forumaker-magicslider.admin.settings.height'),
                help: app.translator.trans('forumaker-magicslider.admin.settings.height_help'),
                min: 100,
              })}

              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.padding_desktop',
                label: app.translator.trans('forumaker-magicslider.admin.settings.padding'),
                help: app.translator.trans('forumaker-magicslider.admin.settings.padding_help'),
                min: 0,
              })}

              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.radius_desktop',
                label: app.translator.trans('forumaker-magicslider.admin.settings.radius'),
                help: app.translator.trans('forumaker-magicslider.admin.settings.radius_help'),
                min: 0,
              })}

              <div className="Form-group">
                <div>
                  <Switch
                    state={disableDesktop}
                    onchange={(v: boolean) => this.setting('forumaker-magicslider.disable_desktop')(v ? '1' : '0')}
                  >
                    {app.translator.trans('forumaker-magicslider.admin.settings.disable_desktop')}
                  </Switch>
                </div>

                <p className="helpText">
                  {app.translator.trans('forumaker-magicslider.admin.settings.disable_desktop_help')}
                </p>
              </div>
            </div>
          </section>

          <section className="MagicSlider-SettingsSection">
            <h3>
              <i className="fas fa-mobile-alt" />
              {app.translator.trans('forumaker-magicslider.admin.settings.section_mobile')}
            </h3>

            <div className="MagicSlider-SettingsSection-content">
              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.height_mobile',
                label: app.translator.trans('forumaker-magicslider.admin.settings.height'),
                help: app.translator.trans('forumaker-magicslider.admin.settings.height_help'),
                min: 100,
              })}

              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.padding_mobile',
                label: app.translator.trans('forumaker-magicslider.admin.settings.padding'),
                help: app.translator.trans('forumaker-magicslider.admin.settings.padding_help'),
                min: 0,
              })}

              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.radius_mobile',
                label: app.translator.trans('forumaker-magicslider.admin.settings.radius'),
                help: app.translator.trans('forumaker-magicslider.admin.settings.radius_help'),
                min: 0,
              })}

              <div className="Form-group">
                <div>
                  <Switch
                    state={disableMobile}
                    onchange={(v: boolean) => this.setting('forumaker-magicslider.disable_mobile')(v ? '1' : '0')}
                  >
                    {app.translator.trans('forumaker-magicslider.admin.settings.disable_mobile')}
                  </Switch>
                </div>

                <p className="helpText">
                  {app.translator.trans('forumaker-magicslider.admin.settings.disable_mobile_help')}
                </p>
              </div>
            </div>
          </section>

          <section className="MagicSlider-SettingsSection">
            <h3>
              <i className="fas fa-cog" />
              {app.translator.trans('forumaker-magicslider.admin.settings.section_behavior')}
            </h3>

            <div className="MagicSlider-SettingsSection-content">
              {this.buildSettingComponent({
                type: 'number',
                setting: 'forumaker-magicslider.autoplay',
                label: app.translator.trans('forumaker-magicslider.admin.settings.autoplay'),
                help: app.translator.trans('forumaker-magicslider.admin.settings.autoplay_help'),
                min: 0,
              })}

              {this.buildSettingComponent({
                type: 'boolean',
                setting: 'forumaker-magicslider.hide_on_tag_pages',
                label: app.translator.trans('forumaker-magicslider.admin.settings.hide_on_tag_pages'),
                help: app.translator.trans('forumaker-magicslider.admin.settings.hide_on_tag_pages_help'),
              })}

              {this.buildSettingComponent({
                type: 'boolean',
                setting: 'forumaker-magicslider.fit_to_layout',
                label: app.translator.trans('forumaker-magicslider.admin.settings.fit_to_layout'),
                help: app.translator.trans('forumaker-magicslider.admin.settings.fit_to_layout_help'),
              })}
            </div>
          </section>

          <section className="MagicSlider-SettingsSection">
            <h3>
              <i className="fas fa-sliders-h" />
              {app.translator.trans('forumaker-magicslider.admin.settings.section_slides')}
            </h3>

            <div className="MagicSlider-SettingsSection-content">
              <div className="Form-group">
                <div className="MagicSlides-toolbar">
                  <Button className="Button Button--primary" onclick={() => this.add()}>
                    <i className="fas fa-plus" /> {app.translator.trans('forumaker-magicslider.admin.settings.add_slide')}
                  </Button>
                </div>

                <div className="MagicSlides-list">
                  {this.slides.map((s, i) => (
                    <div
                      key={s.id}
                      className={'MagicSlides-item' + (this.dragIndex === i ? ' is-dragging' : '')}
                      title={app.translator.trans('forumaker-magicslider.admin.settings.drag') as string}
                      draggable="true"
                      ondragstart={(e: DragEvent) => this.onDragStart(i, e)}
                      ondragover={(e: DragEvent) => this.onDragOver(i, e)}
                      ondragend={() => this.onDragEnd()}
                    >
                      <input
                        className="FormControl"
                        type="text"
                        placeholder={app.translator.trans('forumaker-magicslider.admin.settings.image_placeholder')}
                        value={s.image}
                        oninput={(e: any) => {
                          s.image = e.target.value;
                          this.syncSlides();
                        }}
                      />

                      <div className="MagicSlides-upload">
                        <Button
                          className="Button"
                          loading={this.uploadingId === s.id}
                          onclick={() => (document.getElementById(`magicslider-upload-${s.id}`) as HTMLInputElement)?.click()}
                        >
                          <i className="fas fa-upload" /> {app.translator.trans('forumaker-magicslider.admin.settings.upload')}
                        </Button>

                        <input
                          id={`magicslider-upload-${s.id}`}
                          type="file"
                          accept="image/*"
                          disabled={this.uploadingId === s.id}
                          onchange={(e: any) => {
                            const file = e.target.files?.[0];
                            if (file) {
                              this.uploadImage(file, s);
                            }
                            e.target.value = '';
                          }}
                          style={{ display: 'none' }}
                        />
                      </div>

                      <input
                        className="FormControl MagicSlides-link"
                        type="text"
                        placeholder={app.translator.trans('forumaker-magicslider.admin.settings.link_placeholder')}
                        value={s.link || ''}
                        oninput={(e: any) => {
                          s.link = e.target.value;
                          this.syncSlides();
                        }}
                      />

                      <span
                        className="MagicSlides-clicks"
                        title={app.translator.trans('forumaker-magicslider.admin.settings.clicks') as string}
                      >
                        <i className="fas fa-mouse-pointer" /> {this.clicks[s.id] ?? 0}
                      </span>

                      <Button
                        className={'Button Button--icon MagicSlides-toggle' + (s.newTab ? ' is-active' : '')}
                        icon="fas fa-external-link-alt"
                        title={app.translator.trans('forumaker-magicslider.admin.settings.new_tab') as string}
                        aria-label={app.translator.trans('forumaker-magicslider.admin.settings.new_tab') as string}
                        onclick={() => {
                          s.newTab = !s.newTab;
                          this.syncSlides();
                        }}
                      />

                      <div className="MagicSlides-actions">
                        <Button
                          className="Button Button--icon Button--danger"
                          icon="fas fa-trash"
                          title={app.translator.trans('forumaker-magicslider.admin.settings.delete') as string}
                          aria-label={app.translator.trans('forumaker-magicslider.admin.settings.delete') as string}
                          onclick={() => this.remove(i)}
                        />
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
    this.slides.push({ id: genId(), image: '', link: '', newTab: false });
    this.syncSlides();
    setTimeout(() => m.redraw(), 0);
  }

  remove(i: number) {
    // Native confirm(), same pattern used throughout Arena's admin for
    // destructive actions — no dedicated confirm-modal component in this
    // codebase, and this doesn't need one.
    if (!confirm(app.translator.trans('forumaker-magicslider.admin.settings.confirm_delete') as string)) return;

    const [removed] = this.slides.splice(i, 1);
    this.syncSlides();
    if (removed?.image) this.deleteUploadedImage(removed.image);
  }

  syncSlides() {
    this.setting('forumaker-magicslider.slides')(JSON.stringify(this.slides));
    m.redraw();
  }
}
