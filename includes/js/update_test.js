document.addEventListener('DOMContentLoaded', function() {
    const questionsContainer = document.getElementById('questionsContainer');
    const addQuestionButton = document.getElementById('addQuestionButton');
    const deletedQuestionsInput = document.getElementById('deletedQuestions');
    let questionCount = document.querySelectorAll('.question-block').length;
    let deletedQuestions = [];

    addQuestionButton.addEventListener('click', function() {
        questionCount++;
        const questionBlock = document.createElement('div');
        questionBlock.classList.add('question-block');
        questionBlock.innerHTML = `
            <button type="button" class="delete-button">X</button>
            <label>Question ${questionCount}:</label>
            <textarea name="new_questions[${questionCount}][question_text]" required></textarea>
            <label>Correct Option:</label>
            <input type="text" name="new_questions[${questionCount}][f_option0]" required>
            <label>False Option 1:</label>
            <input type="text" name="new_questions[${questionCount}][f_option1]" required>
            <label>False Option 2:</label>
            <input type="text" name="new_questions[${questionCount}][f_option2]" required>
            <label>False Option 3:</label>
            <input type="text" name="new_questions[${questionCount}][f_option3]" required>
        `;
        questionsContainer.appendChild(questionBlock);
        addDeleteButtonListener(questionBlock.querySelector('.delete-button'));
    });

    function addDeleteButtonListener(button) {
        button.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this question?')) {
                const questionBlock = button.parentElement;
                const questionId = questionBlock.getAttribute('data-question-id');
                if (questionId) {
                    deletedQuestions.push(questionId);
                    deletedQuestionsInput.value = deletedQuestions.join(',');
                }
                questionBlock.remove();
                formChanged = true;
            }
        });
    }

    document.querySelectorAll('.delete-button').forEach(addDeleteButtonListener);

    let formChanged = false;
    const updateTestForm = document.getElementById('updateTestForm');

    updateTestForm.addEventListener('input', function() {
        formChanged = true;
    });

    updateTestForm.addEventListener('submit', function() {
        window.removeEventListener('beforeunload', beforeUnloadHandler);
    });

    function beforeUnloadHandler(e) {
        if (formChanged) {
            const confirmationMessage = 'You have unsaved changes. Are you sure you want to leave without saving?';
            e.returnValue = confirmationMessage;
            return confirmationMessage;
        }
    }

    window.addEventListener('beforeunload', beforeUnloadHandler);
});