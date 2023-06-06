document.addEventListener('DOMContentLoaded', _ => {
  const bt_birthdate_modal = document.querySelector('#bt_birthdate_modal')
  if (!bt_birthdate_modal) return

  let localConfig = getLocalConfig()
  if (localConfig) {
    localConfig = JSON.parse(localConfig)
    const currentDate = new Date()
    const localTime = new Date(localConfig.expiry)

    // Comparar las fechas utilizando getTime() >
    if (localTime.getTime() > currentDate.getTime()) return

  }

  handleCloseButtonModal(bt_birthdate_modal, () => {
    // obtenemos el siguiente dia. podemos añadir mas dias pasando por el parametro
    const newDate = addDay()
    setLocalConfig(JSON.stringify(setConfig({
      expiry: newDate.toISOString()
    })))
  })

  setTimeout(() => {
    bt_birthdate_modal.showModal()
  }, 3000)
})

const setConfig = values => {
  return {
    expiry: '',
    ...values
  }
}

const setLocalConfig = config => localStorage.setItem('bt_birthdate_modal', config)

const getLocalConfig = () => localStorage.getItem('bt_birthdate_modal')

const addDay = (day = 1) => {
  const currentDate = new Date()
  currentDate.setDate(currentDate.getDate() + day)
  return currentDate
}

const handleCloseButtonModal = (modal, callback) => {
  const btnClose = modal.querySelector('#bt_close')
  if (!btnClose) return
  btnClose.addEventListener('click', callback)
}

