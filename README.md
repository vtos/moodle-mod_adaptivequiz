Moodle Adaptive Test Activity
===============================
The Adaptive Test activity enables a teacher to create tests that efficiently measure
the takers' abilities. Adaptive tests are comprised of questions selected from the
question bank that are tagged with a score of their difficulty. The questions are
chosen to match the estimated ability level of the current test-taker. If the
test-taker succeeds on a question, a more challenging question is presented next. If
the test-taker answers a question incorrectly, a less-challenging question is
presented next. This technique will develop into a sequence of questions converging
on the test-taker's effective ability level. The test stops when the test-taker's
ability is determined to the required accuracy.

The Adaptive Test activity uses the ["Practical Adaptive Testing CAT Algorithm" by
B.D. Wright][1] published in *Rasch Measurement Transactions, 1988, 2:2 p.24* and
discussed in John Linacre's ["Computer-Adaptive Testing: A Methodology Whose Time Has
Come."][2] *MESA Memorandum No. 69 (2000)*.

[1]: http://www.rasch.org/rmt/rmt22g.htm
[2]: http://www.rasch.org/memo69.pdf

This Moodle activity module was originally created as a collaborative effort between [Middlebury
College][3] and [Remote Learner][4]. The current repository was forked from
[https://github.com/middlebury/moodle-mod_adaptivequiz][5].

The [MOODLE_400][6] branch of the repository is compatible with Moodle 4.0. Also, all versions starting from 3.8 are supported.

Further support and development of the plugin to be expected.

[3]: http://www.middlebury.edu/
[4]: http://remote-learner.net/
[5]: https://github.com/middlebury/moodle-mod_adaptivequiz
[6]: https://github.com/vtos/moodle-mod_adaptivequiz/tree/MOODLE_400

The Question Bank
-----------------
To begin with, questions to be used with this activity are added or imported into
Moodle's question bank. Only questions that can automatically be graded may be used.
As well, questions should not award partial credit. The questions can be placed in
one or more categories.

This activity is best suited to determining an ability measure along a unidimensional
scale. While the scale can be very broad, the questions must all provide a measure of
ability or aptitude on the same scale. In a placement test for example, questions low
on the scale that novices are able to answer correctly should also be answerable by
experts, while questions higher on the scale should only be answerable by experts or
a lucky guess. Questions that do not discriminate between takers of different
abilities on will make the test ineffective and may provide inconclusive results.

Take for example a language placement test. Low-difficulty vocabulary and
reading-comprehension questions would likely be answerable by all but the most novice
test-takers. Likewise, high-difficulty questions involving advanced gramatical
constructs and nuanced reading-comprehension would be likely only be correctly
answered by advanced, high-level test-takers. Such questions would all be good
candidates for usage in an Adaptive Test. In contrast, a question like "Is 25¥ a good
price for a sandwich?" would not measure language ability but rather local knowledge
and would be as likely to be answered correctly by a novice speaker who has recently
been to China as it would be answered incorrectly by an advanced speaker who comes
from Taiwan -- where a different currency is used. Such questions should not be
included in the question-pool.

Questions must be tagged with a 'difficulty score' using the format
'adpq\_*n*' where *n* is a positive integer, e.g. 'adpq\_1' or 'adpq\_57'. The range
of the scale is arbitrary (e.g. 1-10, 0-99, 1-1000), but should have enough levels to
distinguish between
question difficulties.

The Testing Process
-------------------
The Adaptive Test activity is configured with a fixed starting level. The test will
begin by presenting the test-taker with a random question from that starting level.
As described in [Linacre (2000)][2], it often makes sense to have the starting level
be in the lower part of the difficulty range so that most test-takers get to answer
at least one of the first few questions correctly, helping their moral.

After the test-taker submits their answer, the system calculates the target question
difficulty it will select next. If the last question was answered correctly, the next
question will be harder; if the last question was answered incorrectly, the next
question will be easier. The system also calculates a measure of the test-taker's
ability and the standard error for that measure. A next random question at or near
the target difficulty is selected and presented to the user.

This process of alternating harder questions following correct answers and easier
questions following wrong answers continues until one of the stopping conditions is
met. The possible stopping conditions are as follows:

 * There are no remaining easier questions to ask after a wrong answer.
 * There are no remaining harder questions to ask after a correct answer.
 * The standard error in the measure has become precise enough to stop.
 * The maximum number of questions has been exceeded.

Test Parameters and Operation
==============================

The primary parameters for tuning the operation of the test are:

 * The starting level
 * The minimum number of questions
 * The maximum number of questions
 * The standard error to stop

Relationship between maximum number of questions and Standard Error
--------------------------------------------------------------------
As discussed in [Wright (1988)][1], the formula for calculating the standard error is
given by:

    Standard Error (± logits) = sqrt((R+W)/(R*W))

where `R` is the number of right answers and `W` is the number of wrong answers. This
value is on a [logit](http://en.wikipedia.org/wiki/Logit) scale, so we can apply the
inverse-logit function to convert it to an percentage scale:

    Standard Error (± %) = ((1 / ( 1 + e^( -1 * sqrt((R+W)/(R*W)) ) ) ) - 0.5) * 100

Looking at the Standard Error function, it is important to note that it depends only
on the difference between the number of right and wrong answers and the total number
of answers, not on any other features such as which answers were right and which
answers were wrong. For a given number of questions asked, the Standard Error will be
smallest when half the answers are right and half are wrong. From this, we can deduce
the minimum standard error possible to achieve for any number of questions asked:

 * 10 questions (5 right, 5 wrong) → Minimum Standard Error = ± 15.30%
 * 20 questions (10 right, 10 wrong) → Minimum Standard Error = ± 11.00%
 * 30 questions (15 right, 15 wrong) →  Minimum Standard Error = ± 9.03%
 * 40 questions (20 right, 20 wrong) →  Minimum Standard Error = ± 7.84%
 * 50 questions (25 right, 25 wrong) →  Minimum Standard Error = ± 7.02%
 * 60 questions (30 right, 30 wrong) →  Minimum Standard Error = ± 6.42%
 * 70 questions (35 right, 35 wrong) →  Minimum Standard Error = ± 5.95%
 * 80 questions (40 right, 40 wrong) →  Minimum Standard Error = ± 5.57%
 * 90 questions (45 right, 45 wrong) →  Minimum Standard Error = ± 5.25%
 * 100 questions (50 right, 50 wrong) →  Minimum Standard Error = ± 4.98%
 * 110 questions (55 right, 55 wrong) →  Minimum Standard Error = ± 4.75%
 * 120 questions (60 right, 60 wrong) →  Minimum Standard Error = ± 4.55%
 * 130 questions (65 right, 65 wrong) →  Minimum Standard Error = ± 4.37%
 * 140 questions (70 right, 70 wrong) →  Minimum Standard Error = ± 4.22%
 * 150 questions (75 right, 75 wrong) →  Minimum Standard Error = ± 4.07%
 * 160 questions (80 right, 80 wrong) →  Minimum Standard Error = ± 3.94%
 * 170 questions (85 right, 85 wrong) →  Minimum Standard Error = ± 3.83%
 * 180 questions (90 right, 90 wrong) →  Minimum Standard Error = ± 3.72%
 * 190 questions (95 right, 95 wrong) →  Minimum Standard Error = ± 3.62%
 * 200 questions (100 right, 100 wrong) →  Minimum Standard Error = ± 3.53%

What this listing indicates is that for a test configured with a maximum of 50
questions and a "standard error to stop" of 7%, the maximum number of questions will
always be encountered first and stop the test. Conversely, if you are looking for a
standard error of 5% or better, the test must ask at least 100 questions.

Note that these are best-case scenarios for the number of questions asked. If a
test-taker answers a lopsided run of questions right or wrong the test will require
more questions to reach a target standard of error.

Minimum number of questions
----------------------------
For most purposes this value can be set to `1` since the standard of error to stop
will generally set a base-line for the number of questions required. This could be
configured to be greater than the minimum number of questions needed to achieve the
standard of error to stop if you wish to ensure that all test-takers answer
additional questions.

Starting level
---------------
As mentioned above, this usually will be set in the lower part of the difficulty
range (about 1/3 of the way up from the bottom) so that most test takers will be able to
answer one of the first two questions correctly and get a moral boost from their
correct answers. If the starting level is too high, low-ability users would be asked
several questions they can't answer before the test begins asking them questions at a
level they can answer.

Scoring
========
As discussed in [Wright (1988)][1], the formula for calculating the ability measure is given by:

    Ability Measure = H/L + ln(R/W)

where `H` is the sum of all question difficulties answered, `L` is the number of
questions answered, `R` is the number of right answers, and `W` is the number of
wrong answers.

Note that this measure is not affected by the order of answers, just the total
difficulty and number of right and wrong answers. This measure is dependent on the
test algorithm presenting alternating easier/harder questions as the user answers
wrong/right and may not be applicable to other algorithms. In practice, this means
that the ability measure should not be greatly affected by a small number of spurious
right or wrong answers.

As discussed in [Linacre (2000)][2], the ability measure of the test taker aligns
with the question-difficulty at which the test-taker has a 50% probability of
answering a question correctly.

For example, given a test with levels 1-10 and a test-taker that answered every
question 5 and below correctly and every question 6 and up wrong, the test-taker's
ability measure would fall close to 5.5.

Remember that the ability measure does have error associated with it. Be sure to take the standard error amount into account when acting on the score.

# Custom CAT models

## General information

Apart from the CAT model described above, the activity can be customized to use another logic to assess answers
and select questions when running a quiz. At this moment, this is an experimental, partially implemented feature.
This section describes the ongoing work for those who are interested in this functionality to be present in
the adaptive quiz activity.

The plugin supports sub-plugins placed under `/mod/adaptivequiz/catmodel` directory. Each sub-plugin of such type
is implementation of a custom CAT model. Such sub-plugins are enabled to modify the activity form by adding
custom fields there, validation for those fields and specifying the way those fields are populated when the form is initialized.
Sub-plugin can inject custom logic to be called when an adaptive quiz is created, updated and deleted. Basically, this allows for
processing the custom form fields added by a sub-plugin. Finally, sub-plugins can implement certain hooks to replace the default
logic of administering items (questions) during the quiz and inject their own logic based on some custom algorithms. The ways
a sub-plugin injects such functionality are described in detail below.

## Technical implementation

This section makes the most interest for those who would like to implement certain interfaces to add their custom
CAT models to get used by the adaptive quiz activity. At this point there are three parts of such extension: `mod_form`
customization, hooking up to the mod's lib functions, particularly, `adaptivequiz_add_instance()`,
`adaptivequiz_update_instance()` and `adaptivequiz_delete_instance()`, and the most powerful one - implementing of item
administration interface and a couple of callbacks with specific names placed in sub-plugin's `lib.php`.

### Customization of `mod_form`

A new section has been added to the `mod_form` - 'CAT model'. So far it contains just one field - the sub-plugin selector.
After creating a sub-plugin and placing it under `/mod/adaptivequiz/catmodel` directory, it becomes available in this selector.
After selecting a sub-plugin with a CAT model to use, the `mod_form` is reloaded and tries to pick up implementations of
interfaces, which it expects to be implemented to customize the form. The interfaces are defined under
`/mod/adaptivequiz/classes/local/catmodel/form` dir and `mod_adaptivequiz\local\catmodel\form` namespace.

Below you'll find short description of the interfaces used for `mod_form` customization.

***catmodel_mod_form_modifier***

Used to add custom fields to the form. The fields will be placed right after the CAT model selector.

The interface defines one method to be implemented for adding custom fields:

```
public function definition_after_data_callback(MoodleQuickForm $form): array;
```

In Moodle, when you want to define the `moodleform`'s fields based on values of other fields (like the CAT model selector from above)
you're making use of `moodleform::definition_after_data()` method. The adaptive quiz plugin overrides this method in its `mod_form`
and wires up implementations of `catmodel_mod_form_modifier::definition_after_data_callback()` in it. You can find an example
of implementation of this interface in the 'Hello world' sub-plugin included in the adaptive quiz mod. In general, this
example sub-plugin is a good source of example implementations of the interfaces listed here. Thus, the example source
code isn't listed here, it can be found in the 'Hello world' sub-plugin.

***catmodel_mod_form_validator***

Used to add extra validation the form. Defines one method:

```
public function validation_callback(array $data, array $files): array;
```

Accepts the same parameters as the calling `moodleform_mod::validation()` method, and is expected to return an array
with the same structure as `moodleform_mod::validation()` returns. Again, see the example implementation in 'Hello world'
sub-plugin.

***catmodel_mod_form_data_preprocessor***

The interface defines one method to be implemented:

```
public function data_preprocessing_callback(array $formdefaultvalues): array;
```

In Moodle, to tweak how the `moodleform`'s fields are populated you're making use of `moodleform::data_preprocessing()` method.
The adaptive quiz plugin overrides this method in its `mod_form` and wires up implementations of
`catmodel_mod_form_data_preprocessor::data_preprocessing_callback()` in it. Please, note how the form's values are passed to this
method and that it expects them to be returned modified. As opposed to the calling method passing it be reference. Again, PHPDocs
both in interfaces definition and the sub-plugin's implementations is a good source of information.

You may have noticed that there are several interfaces covering extension of `mod_form` containing just one method. We just follow
the interface segregation principle here, which means a sub-plugin implementation may not necessarily need all methods to be
implemented. For example, it may implement adding of fields to the form, but no validation is needed, etc. This encourages proper
extension design in sub-plugins. Later it can be reviewed whether one interface can go without the others in reality, but for now
such atomic structure is encouraged.

Where those implementations of interfaces should be placed in the sub-plugin's structure? The adaptive quiz plugin searches for
possible implementations under `adaptivequizcatmodel_{your sub-plugin name}\local\catmodel\form` namespace. Thus, the class(es)
should be kept under `/mod/adaptivequiz/catmodel/{your sub-plugin name}/classes/local/catmodel/form` directory. The name of
the class implementing the interfaces does not matter. One class may also implement several interfaces. See the 'Hello world'
plugin to get some tips on how it should be structured.

### Hooking up to the lib functions

After customizing the `mod_form` by adding some fields the next logical step for a CAT model sub-plugin would be processing those
values coming from the form. For this, a sub-plugin may implement several hooks, which are called from the adaptive quiz plugin's
lib functions when creating, updating and deleting a quiz activity instance.

The interfaces, which a sub-plugin may implement to hook up to creation/updating/deleting of an adaptive quiz instance
are listed below. They're defined under `/mod/adaptivequiz/classes/local/catmodel/instance` dir
and `mod_adaptivequiz\local\catmodel\instance` namespace.

***catmodel_add_instance_handler***

Defines one method to be implemented:

```
public function add_instance_callback(stdClass $adaptivequiz, ?mod_adaptivequiz_mod_form $form = null): void;
```

Gets called in adaptive quiz's lib.php in `adaptivequiz_add_instance()`, during creation of an adaptive quiz instance. Here you can
process those custom fields values added by the CAT model sub-plugin, perform some other actions, like triggering specific events,
grades management, etc.

***catmodel_update_instance_handler***

Defines one method:

```
public function update_instance_callback(stdClass $adaptivequiz, ?mod_adaptivequiz_mod_form $form = null): void;
```

Gets called in `adaptivequiz_update_instance()`, the definition and purpose are similar to the `add_instance_callback()` above.

***catmodel_delete_instance_handler***

Defines one method:

```
public function delete_instance_callback(stdClass $adaptivequiz): void;
```

Gets called in `adaptivequiz_delete_instance()`.

In general, the methods of those interfaces accept the same parameters as the adaptive quiz's calling functions. Here again we
follow the interface segregation principle and define several interfaces to implement. As with the `mod_form`, a sub-plugin may
want to implement just one or two interface, depending on its needs.

In a sub-plugin, the implementations of the interfaces listed above are expected to be under
`adaptivequizcatmodel_{your sub-plugin name}\local\catmodel\instance` namespace
and `/mod/adaptivequiz/catmodel/{your sub-plugin name}/classes/local/catmodel/instance` directory.

### Item administration interface

In the process of CAT item administration is basically presenting questions to the test-taker. On the technical level, in
the adaptive quiz activity this is an interface, which has one public method:

```
item_administration::evaluate_ability_to_administer_next_item(?int $previousquestionslot): item_administration_evaluation;
```

The method accepts slot number of the previous administered question. By this slot number an implementation may reach out to
the question engine to evaluate the result (correct/incorrect, fraction, etc.). The `$previousquestionslot` parameter may also be
null. This is the case when attempt has just started and no question has been administered yet. An implementations of
the interface must handle such value as well.

`item_administration::evaluate_ability_to_administer_next_item()` returns an object of specific type -
`item_administration_evaluation`. It has two properties which must be populated depending on the result of evaluating ability
to administer next question:
1. `nextitem` - in case the next item (question) should be administered, this should acquire a value of the `next_item` type.
`next_item` is a very simple value object, containing either an id of the next question to be administered, or a **slot** number
of the next question to be administered. In some case you may already have a slot number of the next question at hand, thus,
with care about performance you pass it instead of question id, because later on the adaptive quiz engine will anyway try to fetch
the slot number from that id, as it operates on slots internally.
2. `stoppagereason` - in case item administration must stop, this property is populated with the string value of the reason
to stop administering questions.

Of course normally only one property should be populated while another one must be null. The class contains a couple of
convenience factory methods to quickly instantiate a corresponding object, check the class' definition.

### Item administration factory

To provide an implementation of item administration described above a sub-plugin is required to implement the following
factory interface:

```
item_administration_factory::item_administration_implementation(
        question_usage_by_activity $quba,
        attempt $attempt,
        stdClass $adaptivequiz
    ): item_administration;
```

The purpose for this interface is to give sub-plugins an ability to instantiate an item administration object with some
constructor parameters, basically, its dependencies. The only public method accepts several arguments - this is the most
general data, which may be required when instantiating an item administration object. Perhaps, it may require more,
suggestions on extending this range of provided data are always welcome! Below is a quick summary of the arguments:
1. `question_usage_by_activity $quba` - a well-known Moodle's part of the question engine
2. `attempt $attempt` - the attempt entity, normally should be used to just read data from using
the `read_attempt_data()->{property}` statement, where `{property}` is a field name from the attempts database table.
3. `stdClass $adaptivequiz` - an activity instance record, but with a couple of extra properties - `context` and `cm` - also 
are well-known Moodle objects.

This data should be sufficient to run item administration, as with its help a sub-plugin may fetch its own data linked to
the attempt, for example (and which normally should be the case).

The returned value is an instance of the class implementing the `item_administration` interface, described in the previous
section.

### Callbacks
The adaptive quiz engine supports a number of callbacks, which may be implemented by sub-plugins to run some specific logic:
1. `post_create_attempt_callback` - can be run when a new attempt has just been created. This may be used by a sub-plugin
to initialize its own data in some way, etc.
2. `post_process_item_result_callback` - can be run when a question answer has been submitted. Please, do not confuse it with
item administration interface, which should decide what next question should be ot stop the attempt. This callback instead
allows a sub-plugin run some intermediate logic between answering questions by the test-taker. For example, update some
calculations in its database, even trigger its own events and whatever. Deciding what the next question should be or whether 
the attempt will stop must still be inside implementation of the item administration interface.
3. `attempts_report_url` - enables a sub-plugin to provide a link to its own attempts report, which will be picked up by
the adaptive quiz activity and displayed as a number of attempts made in the activity's view page for a manager/teacher role.
When a custom sub-plugin is used, the default attempts reporting obviously does not make sense. The sub-plugin being used is fully
responsible for providing proper attempts reporting.

How to define those callbacks in a sub-plugin and where to place them to be picked up by the adaptive quiz engine?
The mechanism is absolutely the same as for any other callback in Moodle. Callbacks in sub-plugins are expected to be in
`lib.php` file. Each callback is a function, starting with sub-plugin's `{component name}`, and ending with `_callback`.
For the `helloworld` sub-plugin it looks like this:

```
function adaptivequizcatmodel_helloworld_post_create_attempt_callback(stdClass $adaptivequiz, attempt $attempt): void
```

```
function adaptivequizcatmodel_helloworld_post_process_item_result_callback(stdClass $adaptivequiz, attempt $attempt): void
```

```
function adaptivequizcatmodel_helloworld_attempts_report_url(stdClass $adaptivequiz, stdClass $cm): ?moodle_url
```

As you can see, the first two callbacks return nothing (they're expected to simply run some logic, no flow control like redirects
or output is expected, just some background actions) and accept two arguments:
1. `stdClass $adaptivequiz` - an activity instance record, but with a couple of extra properties - `context` and `cm` - are
well-known Moodle objects.
2. `attempt $attempt` - the attempt entity, normally should be used to just read data from using
the `read_attempt_data()->{property}` statement, where `{property}` is a field name from the attempts database table.

These callbacks are supposed to fetch all the required data by using the attempt data passed as an argument and then act on its
own way.

The third callback accepts a bit different set of parameters:
1. `stdClass $adaptivequiz` - doesn't require any description at this point.
2. `stdClass $cm` - the well known Moodle's object representing the current course module. This is what's returned by
`get_coursemodule_from_id()` or similar.

The callback is expected to return either `null` or an instance of `moodle_url`, which is actually the URL of the attempts report
provided by the sub-plugin being used. The `null` value can be returned in cases when for some reason the link must not
be displayed (the most common example - missing capabilities).

### Hooking to attempt completion
If some actions (also in background) should be done by a sub-plugin once an attempt is completed, it may handle the adaptive quiz
plugin's even, which is available site-wide - `\mod_adaptivequiz\event\attempt_completed`.

### Future plans
At this point after developing a CAT model sub-plugin and successfully implementing the available interfaces, the reporting pages
will not display any sensible data on the attempts made using custom CAT models. It's to be decided how to handle reporting when
using sub*plugins. Obviously, a sub-plugin should be responsible for displaying the reporting data on its own, and
the adaptive quiz activity should somehow redirect to that reporting. The drawback of the existing visual design of
the adaptive quiz activity is displaying some piece of reporting on its view.php page.

Any feedback on the current sub-plugins structure is always welcome!
