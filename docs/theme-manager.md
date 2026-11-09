# Theme Manager and Smart Tap

The Theme Manager saves separate settings for Company, Worker/Handyman and Admin. Classic with light appearance is the starting theme. Colorful includes service photos. Premium uses teal accents. Each theme supports light, dark and device appearance.

## Staging setup

1. Apply the update to a staging checkout of NextFix Business.
2. Run `php artisan migrate --force` and `php artisan optimize:clear`. Run `php artisan storage:link` if the public storage link is missing.
3. Sign in as a NextFix administrator and open **Administration → Themes**.
4. Choose an application, then adjust its preset, appearance and accent colors. Open the banner editor to set a picture, caption, order and destination for each of its three banners.
5. Review the preview before publishing. Published settings apply on the next page load. **Restore previous theme** swaps the selected app back to its previous settings, including banner images.

Banner destinations are restricted to pages in the selected app. Uploaded images must be JPG, PNG or WebP, at least 480 × 240 pixels and no larger than 5 MB per image. Set the host upload and request limits to accommodate the chosen images. Historical images stay available for restoring the previous settings.

## Smart Tap

Authenticated screens share a Quick actions menu, breadcrumbs and validation summaries. Open Quick actions using its button or Ctrl/Cmd+K. It searches navigation links available to the signed-in role. Eligible tables and job lists offer a filter for the records loaded on the current page. The company booking flow uses photo service choices in Colorful. Dashboard banners support buttons, keyboard navigation and swipes.

The update applies shared styling across the current portals and adds dedicated photo layouts to the dashboards and booking choices. The remaining screen-by-screen redesigns and the Provider portal are tracked in smart-tap-ux-plan.md.

## Staging review

Verify appearance and contrast in each portal on a phone and desktop. Exercise banner publishing and restoration, booking, quotation, payment, report and upload workflows before rolling out the update.
