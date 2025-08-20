# Developer Notes for the Answersheet Question Type

## Overview
The Answersheet question type is a custom Moodle question type designed to display a series of documents (PDF, audio) alongside a grid of possible answers. This question type is particularly useful for exams or assessments where students need to interact with multimedia content and provide structured responses.

## Key Features
- Supports multiple document types (PDF, audio).
- Provides a grid-based interface for answers.
- Allows for flexible configuration of rows, columns, and answer options.
- Includes feedback for correct, partially correct, and incorrect answers.

## Plugin Structure
The plugin follows the standard Moodle question type plugin structure. Below is an overview of the key components:

### Core Files
- **questiontype.php**: Implements the core logic for the question type, including saving, loading, and validation of question data.
- **question.php**: Defines the question class and its behavior during quiz attempts.
- **edit_answersheet_form.php**: Provides the form for creating and editing questions of this type.

### Backup and Restore
- **backup/moodle2/backup_qtype_answersheet_plugin.class.php**: Handles the backup process for this question type.
- **backup/moodle2/restore_qtype_answersheet_plugin.class.php**: Handles the restore process for this question type.

### Utility Classes
- **classes/local/api/answersheet.php**: Contains API methods for managing answersheet data.
- **classes/local/persistent/answersheet_answers.php**: Defines persistent data structures for answers.
- **classes/local/persistent/answersheet_module.php**: Defines persistent data structures for modules.

### Templates
- **templates/answersheet_question.mustache**: Defines the HTML structure for rendering the question during quiz attempts.
- **templates/table/**: Contains sub-templates for rendering table components (columns, rows, etc.).

### JavaScript
- **amd/src/**: Contains JavaScript modules for managing the frontend behavior of the question type.
- **amd/build/**: Contains the minified versions of the JavaScript modules.

### Tests
- **tests/**: Contains PHPUnit and Behat tests for validating the functionality of the plugin.
  - **backup_test.php**: Tests the backup and restore functionality.
  - **qtype_answersheet_edit_form_test.php**: Tests the question editing form.
  - **qtype_answersheet_question_test.php**: Tests the question behavior during quiz attempts.
  - **qtype_answersheet_walkthrough_test.php**: End-to-end tests for the question type.

## Database Schema
The plugin uses custom database tables to store question-specific data. These tables are defined in **db/install.xml** and managed via persistent classes.

### Key Tables
- **qtype_answersheet_answers**: Stores the answers for each question.
- **qtype_answersheet_modules**: Stores metadata about the modules (e.g., documents, audio).

## API Design
The plugin provides a clean API for managing question data. The API is located in **classes/local/api/answersheet.php** and includes methods for:
- Creating and updating answersheet data.
- Retrieving question-specific data for rendering.
- Validating user responses.

## Backup and Restore
The backup and restore functionality ensures that all question data, including multimedia files and grid configurations, are preserved during course backups and restores. This is implemented in the **backup/moodle2/** directory.

## Frontend Behavior
The frontend behavior is managed using JavaScript modules located in **amd/src/**. These modules handle:
- Dynamic rendering of the answer grid.
- Interaction with multimedia elements.
- Validation of user inputs.

## Testing
The plugin includes comprehensive tests to ensure reliability:
- **PHPUnit Tests**: Validate the backend logic, including API methods and database interactions.
- **Behat Tests**: Validate the user interface and end-to-end workflows.

## Development Notes
- Follow Moodle's coding guidelines for PHP, JavaScript, and CSS.
- Use persistent classes for database interactions to ensure data integrity.
- Leverage Moodle's backup and restore APIs for handling course data.
- Write unit tests for all new features and bug fixes.

### Extra Answer Fields and Extra Question Fields

- **`extra_question_fields`**:
  - Adds additional fields to the `qtype_answersheet` table, such as feedback formats, numbering options, and other question-level settings.
  - These fields are included when the question is loaded, saved, or backed up.

- **`extra_answer_fields`**:
  - Adds additional fields to the `qtype_answersheet_answers` table, such as `moduleid`, `sortorder`, `name`, `numoptions`, and more.
  - These fields store detailed information about each answer, including its relationship to modules and additional metadata.

- **Backup and Restore**:
  - During the backup process, both `extra_question_fields` and `extra_answer_fields` are included to ensure all metadata is preserved.
  - **Important Note**: The backup process must first handle the modules (e.g., documents, audio) before backing up the answersheet data. If the modules are not backed up first, the restore process will fail because the answersheet data depends on the module records.

### Loading All Information into the Question

- **Reason**: To avoid repeated database queries during question usage, all necessary data is loaded into the question object at once. This includes:
  - Answersheet data (via `answersheet_api::add_data`).
  - Document data (via `answersheet_docs::add_data`).
  - Combined feedback, hints, and other metadata.

- **Test Helpers**:
  - The test helpers for this question type are designed in two ways:
    1. **Form-Based Question Creation**:
       - Simulates creating a question through the Moodle form interface.
       - Saves the question and its associated data into the database.
    2. **Virtual Question Creation**:
       - Creates a question entirely in memory without saving it to the database.
       - Useful for testing scenarios where database persistence is not required.

- **Implication**:
  - Because of the second type of test helper (virtual question creation), the question object must be fully self-contained. All necessary data must be loaded into the object to ensure it behaves correctly without relying on database queries.

## License
This plugin is licensed under the GNU General Public License v3. See the LICENSE.md file for details.
