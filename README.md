# LaLA: Let(\')s audit Learning Analytics #
"Let's audit Learning Analytics" (LaLA) is a Moodle plugin to enable administrators and auditors of Moodle Learning Analytics 
models to retrieve evidence for their audit, like the sample data collected on the Moodle instance, the calculated features 
and predictions made by the model.

Machine learning models have often been found to be unfair, for example, when they produce more errors for certain groups[^1]. 
To ensure that unfair models are not deployed in Moodle Learning Analytics (LA) and to guarantee the trustworthiness of the 
deployed models, it is crucial to audit their fairness before deployment. 
However, Moodle currently lacks the necessary auditability features, specifically, it does not store and make available 
evidence that can be used to prove or disprove fairness claims. To address this lack of evidence, we developed a plugin 
that allows developers and administrators to train and test an LA model configuration while also storing the intermediate 
results and providing these data sets as downloads. By enabling fairer LA models and increasing trust in their predictions, 
we hope to reach more learners and maximize the potential benefits of these models.

For the extensive documentation of this plugin, see the [Wiki](https://github.com/LiFaytheGoblin/moodle-tool_lala/wiki).

[^1]: Riazy, S. and Simbeck, K. (2019) Predictive Algorithms in Learning Analytics and their Fairness. Gesellschaft für Informatik e.V. Available at: https://doi.org/10.18420/delfi2019_305.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/admin/tool/lala

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2024 Linda Fernsel <fernsel@htw-berlin.de>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.

## Cite as ##
Fernsel, L. (2024) Let's audit Learning Analytics [Moodle Plugin]. Available at: https://github.com/LiFaytheGoblin/moodle-tool_lala/.

```biblatex
@software{lala,
  author = {Linda Fernsel},
  title={Let’s audit Learning Analytics [Moodle Plugin]}, 
  url={https://github.com/LiFaytheGoblin/moodle-tool_lala/}, 
  version = {4.0.1},
  date = {2024-02-05},
}
```

## Acknowledgements ##
This work is funded by the Federal Ministry of Education and Research of Germany as part of the project [Fair Enough?](https://iug.htw-berlin.de/projekte/fair-enough/)
(project nr: 16DHB4002) in Prof. Katharina Simbeck's research group "Informatik und Gesellschaft" at the University of Applied Sciences (HTW) Berlin.
