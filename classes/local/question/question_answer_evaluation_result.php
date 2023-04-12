<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

declare(strict_types=1);

namespace mod_adaptivequiz\local\question;

/**
 * Shapes the result of assessing a question answer.
 *
 * @package    mod_adaptivequiz
 * @copyright  2023 Vitaly Potenko <potenkov@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_answer_evaluation_result {

    /**
     * @var bool $wasanswered
     */
    private $answerwasgiven;

    /**
     * @var bool $answeriscorrect
     */
    private $answeriscorrect;

    /**
     * The constructor. Closed, named constructors must be used instead.
     */
    private function __construct() {
    }

    /**
     * Tells whether an answer was given to the question
     *
     * @return bool
     */
    public function answer_was_given(): bool {
        return $this->answerwasgiven;
    }

    /**
     * Tells whether the answer given is correct.
     *
     * @return bool
     */
    public function answer_is_correct(): bool {
         return $this->answeriscorrect;
    }

    /**
     * Quickly instantiates the result object when no answer was given for the question.
     *
     * Assumes the answer is incorrect when no answer was actually given.
     *
     * @return self
     */
    public static function when_answer_was_not_given(): self {
        $result = new self;
        $result->answerwasgiven = false;
        $result->answeriscorrect = false;

        return $result;
    }

    /**
     * Quickly instantiates the result object when the question was answered correctly.
     *
     * @return self
     */
    public static function when_answer_is_correct(): self {
        $result = new self;
        $result->answerwasgiven = true;
        $result->answeriscorrect = true;

        return $result;
    }

    /**
     * Quickly instantiates the result object when the question was answered incorrectly.
     *
     * @return self
     */
    public static function when_answer_is_incorrect(): self {
        $result = new self;
        $result->answerwasgiven = true;
        $result->answeriscorrect = false;

        return $result;
    }
}
