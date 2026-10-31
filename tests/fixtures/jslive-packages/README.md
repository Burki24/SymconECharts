# JSLive migration fixtures

The 40 packages are anonymized structural captures of the owner's read-only
export acceptance under JSLive 0.111, Symcon 9.1 and PHP 8.5.8. The source
revision was `286e5e9de39190178f510191f0911ee82f74271d` in
[Burki24/JSLive](https://github.com/Burki24/JSLive). Installation/export/object
identities, labels, paths, action identities and timestamps are synthetic.
No household archive values are stored. The fixture reader supplies deterministic
numeric/Boolean values and three synthetic historical samples per query.

Cases 01–06 cover Cartesian charts, 07–10 Pie/Doughnut, 11–14 historical Radar,
15–22 Gauges and 23–40 Progress. Original configuration types, uncommon styles,
bindings and declared archive policies are retained to exercise all 40 variants.
Font/template placeholders are hydrated only at the test package boundary;
production parsing never recognizes those placeholders.

`../jslive-templates` contains eight exact standard source templates as **inert
reference data** under the original GPL-3.0 license in that directory. They are
hashed, never loaded as runnable visualization resources. Their CRLF bytes are
deliberately exempt from text conversion. `../jslive-assets` contains the three
WOFF2 fixtures used by these cases; the SHA-indexed production manifest
`libs/jslive/fonts.json` identifies their families, origins and licenses, with
unchanged license texts in `libs/jslive/licenses`. These font bytes remain
subject to their OFL/Apache licenses, not the project license.

`../jslive-calendar-rules.json` and `../jslive-radar-rules.json` freeze expected
results independently obtained from the unchanged source. The optional reference
parity runner can recompute them against a read-only sibling JSLive checkout.
The ECharts adapters are independent implementations; neither Chart.js,
CanvasGauges nor progressbar.js is shipped or executed by the new targets.

Rendering these fixtures proves executable models and bounded output. It does
not prove pixel equivalence or replace live native-form, Kachel and IPSView
acceptance after installation of the new modules.
